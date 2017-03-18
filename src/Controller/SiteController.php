<?php
namespace BPush\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use BPush\Model\Subscription;

class SiteController extends ControllerBase
{
    public function connect(Application $app)
    {
        $controllers = parent::connect($app, false);

        $controllers->get('/site/{id}/list', function(Request $request, $id) use ($app) {
            $site = $app['repository']->site->find($id);
            $owner = $this->getLoginOwner();
            $unregistered = $request->get('unregistered');
            $notifications = $app['repository']->notification->findSentItemsBySiteId($site->id);
            return $this->render('site_list.twig', array(
                'site' => $site,
                'notifications' => $notifications,
                'unregistered' => $unregistered,
                'login_owner' => $owner
            ));
        });

        $controllers->get('/site/{id}/join', function(Request $request, $id) use ($app) {
            $back = $request->get('back');
            $autoback = $request->get('autoback');
            $site = $app['repository']->site->find($id);
            return $this->render('site_join.twig', array(
                'site' => $site,
                'app_key' => $site->app_key,
                'vapid_public_key' => $app['vapid']['public_key_hex'],
                'endpoint_base' => SERVICE_HOST . ROOT_PATH,
                'back' => $back,
                'autoback' => $autoback
            ));
        });

        $controllers->get('/site/{id}/service_worker', function(Request $request, $id) use ($app) {
            $site = $app['repository']->site->find($id);
            $jsGen = new \BPush\Model\JsGenerator($app);
            $content = $jsGen->generateServiceWorker($site);
            return new Response(
                $content, 200, ['Content-Type' => 'application/javascript']
            );
        });

        $controllers->get('/site/{id}/loader', function(Request $request, $id) use ($app) {
            $callback = $request->get('callback');
            $content = $this->render('embedded/loader.js.twig', [
                'endpoint_base' => SERVICE_HOST . ROOT_PATH,
                'swlib_url' => SERVICE_HOST . ROOT_PATH . '/js/swlib.js',
                'callback' => $callback
            ]);
            return new Response(
                $content, 200, ['Content-Type' => 'application/javascript']
            );
        });

        return $controllers;
    }
}

