<?php
namespace BPush\Model;

class JsGenerator
{
    /** @var \Silex\Application */
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function generateServiceWorker($site)
    {
        $serviceWorkerJs = file_get_contents(PUBLIC_ROOT . '/js/service-worker.js');
        $globalVars  = $this->app['twig']->render('embedded/global.js.twig', array(
            'endpoint_base' => SERVICE_HOST . ROOT_PATH,
            'app_key' => $site->app_key,
            'vapid_public_key' => $this->app['vapid']['public_key_hex'],
            'simple_embedded' => false
        ));
        $js = $globalVars . $serviceWorkerJs;

        return $js;
    }
}

