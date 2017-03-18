<?php
namespace BPush\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ControllerBase implements ControllerProviderInterface
{
    protected $app;
    protected $loginCheckFunction;
    protected $rollbacked = false;
    protected $beginTransaction = false;
    protected $userMessage;
    private $pulledUserMessages = array();

    public function connect(Application $app, $loginCheck=true, $transaction=false)
    {
        $this->userMessage = new \BPush\Model\UserMessage($app, session_id());
        $this->loginCheckFunction = function(Request $request) use($app) {
            $owner = $this->getLoginOwner();
            if ( !$owner ) {
                return new RedirectResponse(ROOT_PATH . '/login');
            }
            $app['owner'] = $owner;
        };
        $this->app = $app;
        $controllers = $app['controllers_factory'];
        if ( $loginCheck ) {
            $controllers->before($this->loginCheckFunction);
        }
        if ( $transaction ) {
            $controllers->before(function() use($app) {
                $app['db']->beginTransaction();
                $this->beginTransaction = true;
            });
            $controllers->after(function() use($app) {
                if ( !$this->rollbacked && $this->beginTransaction ) {
                    $app['db']->commit();
                }
            });
        }
        return $controllers;
    }

    public function getLoginOwner() {
        if ( !(isset($_SESSION['login']) && $_SESSION['login']) ) {
            return false;
        }
        $ownerId = $_SESSION['owner_id'];
        $owner = $this->app['repository']->owner->find($ownerId);
        if ( !$owner ) {
            return false;
        }
        return $owner;
    }

    public function rollback() {
        if ( $this->beginTransaction ) {
            $this->app['db']->rollback();
        }
        $this->rollbacked = true;
    }

    public function render($tpl, $params = array())
    {
        if ( isset($_SESSION['csrf_token']) ) {
            $csrfToken = $_SESSION['csrf_token'];
        }
        $csrfToken = uniqid();
        $_SESSION['CSRF_TOKEN'] = $csrfToken;
        $pathElements = explode('/',$_SERVER['REQUEST_URI']);
        $pageId = array_pop($pathElements);
        $defaultParams = array(
            'ROOT_PATH' => ROOT_PATH,
            'ENVIRONMENT' => BPUSH_ENVIRONMENT,
            'SERVICE_HOST' => SERVICE_HOST,
            'CSRF_TOKEN' => $csrfToken,
            'PAGE_ID' => $pageId,
        );
        $defaultParams = array_merge($defaultParams, $this->pulledUserMessages);
        $params = array_merge($defaultParams, $params);
        return $this->app['twig']->render($tpl, $params);
    }

    public function trans($message,$params = array()) {
        return $this->app['translator']->trans($message, $params);
    }

    public function redirect($path) {
        return new RedirectResponse(ROOT_PATH . $path);
    }

    public function checkCsrf($request)
    {
        $token = $request->get('ctoken');
        if ( $token != $_SESSION['CSRF_TOKEN'] ) {
            throw new \Exception('CSRF Check Error');
        }
    }

    public function pullUserMessage() {
        $this->pulledUserMessages = array(
            'infos' => $this->userMessage->pullInfos(),
            'errors' =>$this->userMessage->pullErrors()
        );
    }

    public function renderJson($arr, $status = 200, $crossOrigin = false) {
        $headers = [];
        if ( $crossOrigin ) {
            $headers['Access-Control-Allow-Origin'] = '*';
        }
        return new JsonResponse(json_encode($arr), $status, $headers);
    }

    public function renderJsonP($arr, $callback, $status = 200, $crossOrigin = false) {
        $r = $this->renderJson($arr, $status, $crossOrigin);
        $r->setCallback($callback);
        return $r;
    }

}


