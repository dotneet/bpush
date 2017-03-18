<?php
namespace BPush\Model;

use Aws\Sns\SnsClient;
use Aws\Sns\Exception\SnsException;
use Aws\Sns\Exception\EndpointDisabledException;
use Aws\Sns\Exception\NotFoundException;

use Minishlink\WebPush\WebPush;

class PushMessage
{
    private $app;
    private $sns;

    /** @var \Aws\Sns\SnsClient */
    private $obj;

    public function __construct($app)
    {
        if ( !$app ) {
            throw new \Exception('must be set $app.');
        }
        $this->app = $app;
        $this->sns = $this->getSnsClient();
    }

    private function getSnsClient()
    {
        return $this->obj = SnsClient::factory(array(
            'version' => 'latest',
            'region' => AWS_REGION,
            'credentials' => array(
              'key'    => AWS_ACCESS_KEY,
              'secret' => AWS_SECRET_ACCESS_KEY
            )
        ));
    }

    public function registerGcm($token)
    {
        $options = array(
            'PlatformApplicationArn' => AWS_SNS_GCM_ARN,
            'Token'                  => $token,
        );
        try {
            $res = $this->sns->createPlatformEndpoint($options);
        } catch (Exception $e) {
            //echo $e->getMessage();
            return false;
        }
        return $res['EndpointArn'];
    }

    public function publish($message, array $arns)
    {
        foreach ( $arns as $arn ) {
            try {
                $this->sns->publish(array(
                    'MessageStructure' => 'json',
                    'TargetArn' => $arn,
                    'Message' => json_encode([
                        'GCM' => json_encode([
                            'data' => [
                                'message' => $message
                            ] 
                        ])
                    ])
                ));
            } catch ( EndpointDisabledException $e ) {
                $this->app['logger']->addInfo("remove arn ${arn} by EndpointDisabledException");
                $this->app['repository']->subscription->deleteByEndpointArn($arn);
            } catch ( NotFoundException $e ) {
                $this->app['logger']->addInfo("remove arn ${arn} by NotFoundException");
                $this->app['repository']->subscription->deleteByEndpointArn($arn);
            } catch ( SnsException $e ) {
                $this->app['logger']->addError($e);
            }
        }
    }

    /**
     * For VAPID protocol
     */
    public function send($site, $notification, array $subscriptions)
    {
        if ( count($subscriptions) == 0 ) {
            return false;
        }

        // WebPush library: https://github.com/web-push-libs/web-push-php
        
        $auth = array(
            'VAPID' => array(
                'subject' => $site->url,
                'publicKey' => $this->app['vapid']['public_key_base64'],
                'privateKey' => $this->app['vapid']['private_key_base64']
            )
        );

        if ( defined('GOOGLE_API_KEY') ) {
            $auth['GCM'] = GOOGLE_API_KEY;
        }

		$site_id = $subscriptions[0]['site_id'];
		$defaultOptions = array(
            'TTL' => 24*60*60, 			// defaults to 4 weeks
            'urgency' => 'normal', 	// protocol defaults to "normal"
            'topic' => 'bpush_site_' . $site_id 					// not defined by default
		);
        $webPush = new WebPush($auth, $defaultOptions);

        $icon = SERVICE_HOST . ROOT_PATH . '/icon_256.png';
        if ( $site->icon ) {
            $icon = SERVICE_HOST . ROOT_PATH . '/siteicons/' . $site->icon;
        }
        $subject = $notification->subject;
        $message = $notification->content;
        $payloadArray = [
            'id' => $notification->id,
            'subject' => $subject,
            'body' => $message,
            'icon' => $icon,
            'tag' => 'bpush_' . $site->id
          ];
        if ( !empty($notification->image_url) ) {
            $payloadArray['image'] = $notification->image_url;
        }
        if ( !empty($site->badge) ) {
            $payloadArray['badge'] = SERVICE_HOST . ROOT_PATH . '/siteicons/' . $site->badge;
        }
        $payload = json_encode($payloadArray);
        $notifications = array_map(function($s) use($payload) {
            return array(
              'endpoint' => $s->endpoint,
              'payload' => $payload,
              'userPublicKey' => $s->p256dh,
              'userAuthToken' => $s->auth_token
            );
        }, $subscriptions);

        foreach ( $notifications as $n ) {
            $webPush->sendNotification($n['endpoint'], $n['payload'], $n['userPublicKey'], $n['userAuthToken']);
        }

        try {
            // flush() returns true if there were no errors.
            $results = $webPush->flush();
            if ( is_array($results) ) {
                foreach ( $results as $r ) {
                    if ( $r['success'] === false && $r['statusCode'] == 410 ) {
                        $this->app['repository']->subscription->deleteByEndpoint($r['endpoint']);
                    }
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            $app['logger']->addError($e);
        }

        return true;
    }
}
