<?php
namespace BPush\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class BasicAuthChecker {
    /** @var \Silex\Application */
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function __invoke()
    {
        $key = 'admin_login_failure_times';
        $failureTimes = $this->app['redis']->get($key);
        if ( $failureTimes > 5 ) {
            return new Response('Forbidden', 401);
        }

        if ( !isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ) {
            header("WWW-Authenticate: Basic realm='bpush'");
            return new Response('Not Authorised', 401);
        } else {
            $users = array(
              ADMIN_USER => ADMIN_PASSWORD
            );

            if ( $users[$_SERVER['PHP_AUTH_USER']] !== $_SERVER['PHP_AUTH_PW'] ) {
                $this->app['redis']->incr($key);
                $this->app['redis']->expire($key, 30);
                header("WWW-Authenticate: Basic realm='bpush'");
                return new Response('Forbidden', 401);
            }
        }
    }
}

