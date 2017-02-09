<?php
namespace BPush\Controller;

use BPush\Middleware\BasicAuthChecker;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminController extends ControllerBase
{
    public function connect(Application $app)
    {
        $controllers = parent::connect($app, false);
        $controllers->before(new BasicAuthChecker($app));

        $controllers->get('/', function(Request $request) use ($app) {

            $ownerCount = $app['repository']->owner->count();
            $subscriptionCount = $app['repository']->subscription->count();
            $siteCount = $app['repository']->site->count();
            $notificationCount = $app['repository']->notification->count();

            return $this->render('admin/index.twig', array(
                'owner_count' => $ownerCount,
                'site_count' => $siteCount,
                'subscription_count' => $subscriptionCount,
                'notification_count' => $notificationCount
            ));
        });

        return $controllers;
    }
}

