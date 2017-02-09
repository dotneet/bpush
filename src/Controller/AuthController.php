<?php
namespace BPush\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use BPush\Model\Owner;
use BPush\Model\Mailer;

class AuthController extends ControllerBase
{
    public function connect(Application $app)
    {
        $controllers = parent::connect($app, false, true);

        $controllers->get('/login', function(Request $request) use ($app) {

            // if logged in move to owner page.
            if ( $_SESSION['login'] ) {
                $ownerId = $_SESSION['owner_id'];
                if ( $ownerId ) {
                    $owner = $app['repository']->owner->find($ownerId);
                    if ( $owner->suspended == null && $owner->status == Owner::STATUS_CONFIRMED ) {
                        return new RedirectResponse(ROOT_PATH . '/owner/');
                    }
                }
            }

            return $this->render('login.twig');
        });

        $controllers->get('/signup', function(Request $request) use ($app) {
            return $this->render('signup.twig');
        });

        $controllers->post('/login', function(Request $request) use ($app) {
            $mail = $request->get('mail');
            $password = $request->get('password');
            $owner = $app['repository']->owner->findByMail($mail);
            if ( $owner && $owner->canLogin() && $owner->verifyPassword($password) ) {
                $_SESSION['login'] = 1;
                $_SESSION['owner_id'] = $owner->id;
                return new RedirectResponse(ROOT_PATH . '/owner/');
            }
            $msg = $this->trans('errors.login_failed');
            return $this->render('login.twig', array('errors'=>array($msg)));
        });

        $controllers->get('/logout', function(Request $request) use ($app) {
            $_SESSION['login'] = 0;
            $_SESSION['owner_id'] = false;
            return new RedirectResponse(ROOT_PATH . '/');
        });

        $controllers->post('/signup', function(Request $request) use ($app) {
            if ( !USE_REGISTERATION_THROUGH_WEB ) {
                $msg = $this->trans('errors.operation_is_not_allowed');
                return $this->render('signup.twig', array(
                    'mail' => $mail,
                    'errors' => array($msg)
                ));
            }
            $mail = trim($request->get('mail'));
            $password = trim($request->get('password'));
            if ( !Owner::validateMail($mail) ){
                $msg = $this->trans('errors.mail_address_is_incorrect');
                return $this->render('signup.twig', array(
                    'mail' => $mail,
                    'errors' => array($msg)
                ));
            }
            if ( !Owner::validatePassword($password) ) {
                $msg = $this->trans('errors.password_is_incorrect');
                return $this->render('signup.twig', array(
                    'mail' => $mail,
                    'errors' => array($msg),
                ));
            }
            $owner = $app['repository']->owner->findByMail($mail);
            if ( $owner && $owner->status != Owner::STATUS_UNCONFIRM ) {
                $msg = $this->trans('errors.email_is_already_used');
                return $this->render('signup.twig', array(
                    'owner'=>$owner,
                    'mail' => $mail,
                    'errors' => array($msg)
                ));
            }
            if ( $owner ) {
                $owner->updatePassword($password);
            } else {
                $owner = $app['repository']->owner->create($mail, $password);
            }
            try {
                $template = 'mail/signup_' . DEFAULT_LOCALE . '.twig';
                $mailBody = $this->render($template, ['owner'=>$owner]);
                if ( !Mailer::send($mail, $this->trans('registeration_confirmation'), $mailBody) ) {
                    $app['logger']->addWarning('mail error. mail=' . $mail);
                }
            } catch (\Exception $e) {
                $app['logger']->addError($e);
                $this->rollback();
                $msg = $this->trans('errors.mail_sending_is_failed');
                return $this->render('signup.twig', array(
                    'owner'=>$owner,
                    'mail' => $mail,
                    'errors' => array($msg)
                ));
            }
            return $this->render('signup_done.twig', array('owner'=>$owner));
        });

        $controllers->get('/auth/password_reset', function(Request $request) use ($app) {
            $token = $request->get('token');
            $mail = $app['redis']->get('password_reset/' . $token);
            $errors = [];
            if ( !$mail ) {
                $errors = array('found invalid token.');
                return $this->render('password_reset.twig', ['errors' => $errors]);
            }

            $owner = $app['repository']->owner->findByMail($mail);
            if ( !$owner ) {
                $errors = array('found invalid token.');
                return $this->render('password_reset.twig', ['errors' => $errors]);
            }

            return $this->render('password_reset.twig', array(
                'reset_token' => $token,
                'errors' => $errors
            ));
        });

        $controllers->post('/auth/password_reset', function(Request $request) use ($app) {
            $token = $request->get('reset_token');
            $password = $request->get('password');
            $mail = $app['redis']->get('password_reset/' . $token);
            $errors = [];
            if ( !$mail ) {
                $errors = array('found invalid token');
                return $this->render('password_reset.twig', ['errors' => $errors]);
            }

            $owner = $app['repository']->owner->findByMail($mail);
            if ( !$owner ) {
                $errors = array('found invalid token');
                return $this->render('password_reset.twig', ['errors' => $errors]);
            }

            if ( !Owner::validatePassword($password) ) {
                $msg = $this->trans('errors.mail_address_is_incorrect');
                $errors = array($msg);
                return $this->render('password_reset.twig', ['errors' => $errors]);
            }

            $app['repository']->owner->updatePassword($owner->id, $password);
            $_SESSION['login'] = 1;
            $_SESSION['owner_id'] = $owner->id;
            $msg = $this->trans('messages.new_password_is_set');
            $this->userMessage->addInfo($msg);
            return new RedirectResponse(ROOT_PATH . '/owner/');
        });

        $controllers->get('/auth/request_password_reset', function(Request $request) use ($app) {
            return $this->render('request_password_reset.twig');
        });

        $controllers->post('/auth/request_password_reset', function(Request $request) use ($app) {
            $this->checkCsrf($request);
            $mail = $request->get('mail');
            $owner = $app['repository']->owner->findByMail($mail);
            if ( !($owner && $owner['status'] == Owner::STATUS_CONFIRMED) ) {
                $msg = $this->trans('errors.email_is_not_found');
                $this->userMessage->addError($msg);
                return new RedirectResponse(ROOT_PATH . '/auth/request_password_reset');
            }
            
            try {
                $key = sha1($owner->mail);
                $token = uniqid() . uniqid();
                $app['redis']->setex('password_reset/' . $token, 60*30, $mail);
                $template = 'mail/password_reset_' . DEFAULT_LOCALE . '.twig';
                $mailBody = $this->render($template, ['token'=>$token,'owner'=>$owner]);
                if ( !Mailer::send($mail, $this->trans('password_reset'), $mailBody) ) {
                    $app['logger']->addWarning('mail error. mail=' . $mail);
                }
            } catch (\Exception $e) {
                $app['logger']->addError($e);
                $this->rollback();
                $msg = $this->trans('errors.mail_sending_failed');
                return $this->render('signup.twig', array(
                    'owner'=>$owner,
                    'mail' => $mail,
                    'error' => $msg
                ));
            }

            return new RedirectResponse(ROOT_PATH . '/auth/request_password_reset_done');
        });

        $controllers->get('/auth/request_password_reset_done', function(Request $request) use ($app) {
            return $this->render('request_password_reset_done.twig');
        });

        $controllers->get('/auth/confirm', function(Request $request) use ($app) {
            $token = $request->get('token');

            $owner = $app['repository']->owner->findByConfirmToken($token);
            if ( $owner && $owner['confirm_token'] == $token ) {
                $owner->confirm();
                $msg = $this->trans('messages.registeration_is_done');
                $this->userMessage->addInfo($msg);
                $_SESSION['login'] = 1;
                $_SESSION['owner_id'] = $owner->id;
                return $this->redirect('/owner/account');
            }
            return $this->redirect('/');
        });

        return $controllers;
    }
}

