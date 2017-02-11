<?php
namespace BPush\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class OwnerApiController extends ControllerBase
{
    const TOKEN_HEAD_NAME = 'X-BPUSH-TOKEN';

    public function connect(Application $app)
    {
        $controllers = parent::connect($app, false);

        $controllers->post('/send', function(Request $request) use ($app) {
            $owner = $this->getOwnerFromToken($request);
            if ( $owner === false ) {
                return $this->renderJson(["message" => "Unauthorized"], 401);
            }

            $req = json_decode($request->getContent(),true);

            if ( !$req ) {
                return new JsonResponse(["message" => "message format is invalid."], 400);
            }
            $errorField = $this->checkFieldExists($req, ['site_id', 'subject', 'body', 'link_url'] );
            if ( $errorField != null ) {
                return new JsonResponse(["message" => "$errorField field not found."], 400);
            }

            $site = $app['repository']->site->find($req['site_id']);
            if ( !$site || $site['owner_id'] != $owner->id ) {
                return new JsonResponse(["message" => "site_id is invalid."], 400);
            }

            if ( !$owner->canSending($site) ) {
                return new JsonResponse([
                    "status" => "failed",
                    "message" => "leach to limitation of max send messages."
                ]);
            }

            $imageUrl = null;
            if ( isset($req['image_url']) ) {
                $imageUrl = $req['image_url'];
            }
            $notification = $app['repository']->notification->create($req['site_id'], $req['subject'], $req['body'], $req['link_url'], $imageUrl, null);
            $notification->send();

            return new JsonResponse([
                'status'=>'success',
                'message'=>'request was accepted'
            ]);
        });

        $controllers->post('/send_by_tags', function(Request $request) use ($app) {
            $owner = $this->getOwnerFromToken($request);
            if ( $owner === false ) {
                return $this->renderJson(["message" => "Unauthorized"], 401);
            }

            $req = json_decode($request->getContent(),true);

            if ( !$req ) {
                return new JsonResponse(["message" => "message format is invalid."], 400);
            }
            $errorField = $this->checkFieldExists($req, ['site_id', 'subject', 'body', 'link_url', 'tags'] );
            if ( $errorField != null ) {
                return new JsonResponse(["message" => "$errorField field not found."], 400);
            }

            $site = $app['repository']->site->find($req['site_id']);
            if ( !$site || $site['owner_id'] != $owner->id ) {
                return new JsonResponse(["message" => "site_id is invalid."], 400);
            }

            $imageUrl = null;
            if ( isset($req['image_url']) ) {
                $imageUrl = $req['image_url'];
            }
            $notification = $app['repository']->notification->create($req['site_id'], $req['subject'], $req['body'], $req['link_url'], $imageUrl, null, false);
            $notification->send(['tags' => $req['tags']]);

            return new JsonResponse([
                'status'=>'success',
                'message'=>'request was accepted'
            ]);
        });

        $controllers->post('/get_subscriptions_by_tags', function(Request $request) use ($app) {
            $owner = $this->getOwnerFromToken($request);
            if ( $owner === false ) {
                return $this->renderJson(["message" => "Unauthorized"], 401);
            }
            $req = json_decode($request->getContent(),true);

            $site = $app['repository']->site->find($req['site_id']);
            if ( !$site || $site['owner_id'] != $owner->id ) {
                return new JsonResponse(["message" => "site_id is invalid."], 400);
            }

            $subscriptions = $app['repository']->subscription->findByTags($site->id, $req['tags']);
            foreach ( $subscriptions as &$s ) {
                unset($s['p256dh']);
                unset($s['auth_token']);
                unset($s['endpoint']);
                unset($s['endpoint_arn']);
            }
            return $this->renderJson([
                'status' => 'success',
                'subscriptions' => $subscriptions
            ]);
        });

        return $controllers;
    }

    public function checkFieldExists($values, array $fields)
    {
        foreach ( $fields as $field ) {
            if ( !isset($values[$field]) || !$values[$field] ) {
                return $field; 
            }
        }
        return null;
    }

    public function getOwnerFromToken($request)
    {
        $token = $request->headers->get(self::TOKEN_HEAD_NAME);
        if ( empty($token) ) {
            return $this->renderJson(['message' => 'Unauthorized'], 401);
            return false;
        }
        $tokenInfo = $this->app['repository']->ownerToken->findByApiToken($token);
        if ( !$tokenInfo ) {
            return $this->renderJson(["message" => "Unauthorized"], 401);
            return false;
        }
        $ownerId = $tokenInfo['owner_id'];
        $owner = $this->app['repository']->owner->find($ownerId);
        if ( !$owner ) {
            return false;
            return new JsonResponse(["message" => "Unauthorized"], 401);
        }
        return $owner;
    }
}

