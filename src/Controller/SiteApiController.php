<?php
namespace BPush\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use BPush\Model\Subscription;

class SiteApiController extends ControllerBase
{
    public function connect(Application $app)
    {
        $controllers = parent::connect($app, false);

        $controllers->get('/sapi/v1/register_subscription', function(Request $request) use ($app) {
            $appKey = $request->get('app_key');
            $callback = $request->get('cb');
            $json = $request->get('data');
            $visitorId = $request->get('visitor_id');
            $data = json_decode($json, true);
            if ( isset($data['endpoint']) ) {
              $subscriptionId = array_pop(explode('/',$data['endpoint']));
            } else {
              $subscriptionId = $data['subscriptionId'];
            }
            if ( empty($subscriptionId) ) {
                return $this->renderJsonP(['status'=>'failed'], $callback, 400, true);
            }
            $site = $app['repository']->site->findByAppKey($appKey);
            $ipAddr = null;
            if ( isset($_SERVER['HTTP_X_REAL_IP']) ) {
                $ipAddr = $_SERVER['HTTP_X_REAL_IP'];
            }
            $ua = null;
            if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
                $ua = $_SERVER['HTTP_USER_AGENT'];
            }
            $locale = null;
            if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
                $locale = \Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            }
            $subscription = $app['repository']->subscription->create($site->id, $visitorId, $data, $subscriptionId, $ipAddr, $ua, $locale);
            return $this->renderJsonP(['status'=>'success', 'visitor_id' => $subscription->visitor_id], $callback);
        });

        $controllers->get('/sapi/v1/count_receive', function(Request $request) use ($app) {
            $appKey = $request->get('app_key');
            $nid = $request->get('nid');

            $site = $app['repository']->site->findByAppKey($appKey);
            $notification = $app['repository']->notification->find($nid);
            if ( $site && $notification && $notification->site_id == $site->id ) {
                $app['repository']->notification->increaseReceivedCountBuffer($notification->id);
            }

            return $this->renderJson(['status'=>'success'], 200, true);
        });

        $controllers->get('/sapi/v1/get_notification', function(Request $request) use ($app) {
            $appKey = $request->get('app_key');
            $nid = $request->get('nid');
            $cache = $app['repository']->site->getSiteJsonCache($appKey, $nid);
            if ( $cache ) {
                $app['repository']->notification->increaseReceivedCountBuffer($cache['notification']['id']);
                return new JsonResponse($cache, 200, ['Access-Control-Allow-Origin' => '*']);
            }
            $site = $app['repository']->site->findByAppKey($appKey);

            if ( empty($nid) ) {
              $notification = $app['repository']->notification->findLastSentItem($site->id);
            } else {
              $notification = $app['repository']->notification->find($nid);
            }
            $json = null;
            if ( $notification ) {
                $app['repository']->notification->increaseReceivedCountBuffer($notification->id);
                $icon = 'https://' . DOMAIN_NAME . ROOT_PATH . '/icon_256.png';
                if ( $site->icon ) {
                    $icon = 'https://' . DOMAIN_NAME . ROOT_PATH . '/siteicons/' . $site->icon;
                }
                $json = [
                    'error' => false,
                    'notification' => [
                        'id' => $notification->id,
                        'subject' => $notification->subject,
                        'body' => $notification->content,
                        'icon' => $icon,
                        // if multipel notifications received at the same time has a same tag, browser collapses notifications.
                        'tag' => 'bpush_' . $site->id, 
                    ]
                ];

                $app['repository']->site->setSiteJsonCache($appKey, $nid, $json);
                return $this->renderJson($json, 200, true);
            } else {
                $json = [
                    'error' => "notification not found."
                ];
                return $this->renderJson($json, 404, true);
            }
        });

        // this request is called when notification is clicked or touched.
        $controllers->get('/sapi/v1/click', function(Request $request) use ($app) {
            $appKey = $request->get('app_key');
            $nid = $request->get('nid');
            $site = $app['repository']->site->findByAppKey($appKey);
            $notification = false;
            if ( $nid != 'undefined' ) {
                $notification = $app['repository']->notification->find($nid);
            }
            if ( !$site ) {
                return new Response('404 Not Found', 404);
            }
            if ( !$notification ) {
                if ( $site->use_list_page ) {
                    return $this->redirect("/site/${id}/list");
                } else {
                    return $app->redirect($site->url);
                }
            }
            $app['repository']->notification->increaseJumpCountBuffer($notification->id);
            if ( $site->use_list_page ) {
                return $this->redirect("/site/{$site->id}/list");
            } else {
                return $app->redirect($notification->post_url);
            }
        });

        $controllers->get('/sapi/v1/set_visitor_tag', function(Request $request) use ($app) {
            $appKey = $request->get('app_key');
            $visitorId = $request->get('visitor_id');
            $tags = $request->get('tags');
            $callback = $request->get('cb');
            $site = $app['repository']->site->findByAppKey($appKey);

            $inputCheck = $site && !empty($visitorId) && !empty($tags);
            if ( !$inputCheck ) {
                return $this->renderJsonP(['status' => 'failed'], $callback, 400, true);
            }

            try {
                $tags = explode(',', $tags);
                $app['repository']->visitorTag->create($site->id, $visitorId, $tags);
            } catch (\Exception $e) {
                $app['logger']->addError($e);
                $this->renderJsonP(['status' => 'failed'], $callback, 400, true);
            }

            return $this->renderJsonP(['status' => 'success'], $callback, 200, true);
        });


        return $controllers;
    }
}

