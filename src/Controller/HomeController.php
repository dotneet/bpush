<?php
namespace BPush\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use BPush\Model\ServiceInformation;

class HomeController extends ControllerBase
{
    public function connect(Application $app)
    {
        $controllers = parent::connect($app, false);
        $controllers->get('/', function(Request $request) use ($app) {
            if ( !empty(LOGIN_ACCESS_KEY) && $request->get('key') != LOGIN_ACCESS_KEY ) {
              return $this->render('no_access_key.twig');
            }
            return $this->redirect('/login');
        });
        return $controllers;
    }
}

