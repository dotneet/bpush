<?php
namespace BPush\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use BPush\Model\JsGenerator;

class OwnerController extends ControllerBase
{
    public function connect(Application $app)
    {
        $controllers = parent::connect($app, true, true);

        $controllers->get('/owner/', function(Request $request) use ($app) {
            return $this->redirect('/owner/dashboard');
        });

        $controllers->get('/owner/dashboard', function(Request $request) use ($app) {
            $siteId = $this->getSelectedSiteId($request);
            if ( empty($siteId) ) {
                return $this->redirect('/owner/account');
            }
            $page = $request->get('page');
            if ( $page == null ) {
                $page = 0;
            }
            $owner = $app['owner'];
            list($sites, $selectedSite) = $this->getSites($app, $siteId);
            if ( empty($sites) ) {
                return $this->redirect('/owner/account');
            }
            $targetCount = 0;
            $sendCount = 0;
            $sentTotal = 0;
            if ( $selectedSite ) {
                $sendCounts = $app['repository']->sendLog->countInToday(array($selectedSite->id));
                $sendCount = isset($sendCounts[$selectedSite->id]) ? $sendCounts[$selectedSite->id] : 0;
                $sendTargetCounts = $app['repository']->sendLog->getAmountOfSentMessagesInMonth(array($selectedSite->id), date('Y-m'));
                $sentTotal = isset($sendTargetCounts[$selectedSite->id]) ? $sendTargetCounts[$selectedSite->id] : 0;
                $targetCount = $app['repository']->subscription->countBySiteId($selectedSite->id);
            }

            $infos = $this->userMessage->pullInfos();
            $errors = $this->userMessage->pullErrors();

            $sites = $app['repository']->site->findByOwnerId($owner->id);
            return $this->render('owner_dashboard.twig',array(
                'sites' => $sites,
                'selected_site' => $selectedSite,
                'target_count' => $targetCount,
                'sent_total' => $sentTotal,
                'infos' => $infos,
                'errors' => $errors,
            ));
        });

        $controllers->get('/owner/notifications', function(Request $request) use ($app) {
            $siteId = $this->getSelectedSiteId($request);
            $page = $request->get('page');
            if ( $page == null ) {
                $page = 0;
            }
            $owner = $app['owner'];
            list($sites, $selectedSite) = $this->getSites($app, $siteId);
            $targetCount = 0;
            $sendCount = 0;
            if ( $selectedSite ) {
                $sendCounts = $app['repository']->sendLog->countInToday(array($selectedSite->id));
                $sendCount = isset($sendCounts[$selectedSite->id]) ? $sendCounts[$selectedSite->id] : 0;
                $targetCount = $app['repository']->subscription->countBySiteId($selectedSite->id);
            }

            $infos = $this->userMessage->pullInfos();
            $errors = $this->userMessage->pullErrors();

            $notifications = array();
            if ( $selectedSite ) {
                $siteId = $selectedSite['id'];
                $notificationCount = $app['repository']->notification->countBySiteId($siteId);
                if ( $notificationCount > 0 ) {
                    $notifications = $app['repository']->notification->findBySiteId($siteId, $page*10, 10);
                }
            }
            $notifications = ipull($notifications, null, 'id');

            $totalAmountOfSentMessages = $owner->getAmountOfSentMessagesInMonth();
            $amountOfSentMessages = $selectedSite->getAmountOfSentMessagesInMonth();
            $sites = $app['repository']->site->findByOwnerId($owner->id);
            return $this->render('owner_notifications.twig',array(
                'sites' => $sites,
                'selected_site' => $selectedSite,
                'notifications' => $notifications,
                'notification_count' => $notificationCount,
                'page' => $page,
                'target_count' => $targetCount,
                'total_amount_of_sent_messages' => $totalAmountOfSentMessages,
                'amount_of_sent_messages' => $amountOfSentMessages,
                'infos' => $infos,
                'errors' => $errors,
            ));
        });
        
        $controllers->get('/owner/stats', function(Request $request) use ($app) {
            $siteId = $this->getSelectedSiteId($request);
            list($sites, $selectedSite) = $this->getSites($app, $siteId);

            $year = $request->get('year');
            $month = $request->get('month');
            if ( $year && $month ) {
              $time = new \DateTime("$year/$month/01 00:00:00");
            } else {
              $time = new \DateTime(date('Y/m/01 00:00:00'));
            }
            $startOfMonth = clone $time;
            $endOfMonth = (clone $time)->modify('last day of this months');
            
            $stats = $app['repository']->notification->countByDay($siteId, $startOfMonth, $endOfMonth);

            $monthTotalSendingCount = 0;
            $monthTotalReceiveCount = 0;
            $monthTotalJumpCount = 0;
            foreach ( $stats as $stat ) {
                $monthTotalSendingCount += $stat['sending_count'];
                $monthTotalReceiveCount += $stat['total_receive_count'];
                $monthTotalJumpCount += $stat['total_jump_count'];
            }

            $nextMonth = (clone $time)->modify('next months');
            $prevMonth = (clone $time)->modify('previous months');

            return $this->render('owner_stats.twig',array(
                'sites' => $sites,
                'selected_site' => $selectedSite,
                'year' => $startOfMonth->format('Y'),
                'month' => $startOfMonth->format('m'),
                'prev_month' => $prevMonth,
                'next_month' => $nextMonth,
                'month_total_sending_count' => $monthTotalSendingCount,
                'month_total_receive_count' => $monthTotalReceiveCount,
                'month_total_jump_count' => $monthTotalJumpCount,
                'stats' => $stats
            ));
        });

        $controllers->get('/owner/settings', function(Request $request) use ($app) {
            $owner = $app['owner'];
            $apiTokenInfo = $app['repository']->ownerToken->findByOwnerId($owner->id);
            list($sites, $selectedSite) = $this->getSites($app);

            $setupCode = '';
            $setupCode = $this->render('js/tag.js.twig', array('site'=>$selectedSite));
            $rssInfo = $app['repository']->siteRss->findBySiteId($selectedSite->id);

            $this->pullUserMessage();
            return $this->render('owner_settings.twig',array(
                'selected_site' => $selectedSite,
                'sites' => $sites,
                'rss_info' => $rssInfo,
                'api_token_info' => $apiTokenInfo,
                'setup_code' => $setupCode,
            ));
        });

        $controllers->get('/owner/account', function(Request $request) use ($app) {
            $owner = $app['owner'];
            $siteId = $this->getSelectedSiteId($request);
            list($sites, $selectedSite) = $this->getSites($app, $siteId);

            $infos = $this->userMessage->pullInfos();
            $errors = $this->userMessage->pullErrors();

            $apiTokenInfo = $app['repository']->ownerToken->findByOwnerId($owner->id);

            return $this->render('owner_account.twig',array(
                'owner' => $owner,
                'sites' => $sites,
                'selected_site' => false,
                'api_token_info' => $apiTokenInfo,
                'infos' => $infos,
                'errors' => $errors,
            ));
        });

        $controllers->get('/owner/select_site', function(Request $request) use ($app) {
            $siteId = $request->get('id');
            $_SESSION['selected_site_id'] = $siteId;
            return $this->redirect('/owner/dashboard');
        });

        $controllers->post('/owner/site/post', function(Request $request) use ($app) {
            $this->checkCsrf($request);

            $siteId = $request->get('site_id');
            $siteName = $request->get('site_name');
            $siteUrl = $request->get('site_url');
            $useListPage = $request->get('use_list_page');
            $useListPage = $useListPage ? 1 : 0;

            if ( strlen(trim($siteName)) == 0 || strlen(trim($siteUrl)) == 0 ) {
                $this->userMessage->addError($this->trans('errors.invalid_input'));
                return $this->redirect('/owner/account');
            }

            if ( $siteId ) {
                $site = $app['repository']->site->find($siteId);
                if ( $site->owner_id != $app['owner']->id ) {
                    $this->userMessage->addError($this->trans('errors.invalid_input'));
                    return $this->redirect('/owner/account');
                }
            } else {
                $sites = $app['repository']->site->findByOwnerId($app['owner']->id);
                if ( count($sites) >= MAX_SITES_PER_OWNER ) {
                    $this->userMessage->addError('サイトの登録は' . MAX_SITES_PER_OWNER . 'つまでです');
                    return $this->redirect('/owner/account');
                }
            }

            try {
                if ( $siteId ) {
                    $app['repository']->site->update($siteId, $siteName, $siteUrl, null, $useListPage);
                    $this->userMessage->addInfo($this->trans('messages.site_information_is_updated'));
                    return $this->redirect('/owner/settings');
                } else {
                    $app['repository']->site->create($app['owner']->id, $siteName, $siteUrl, 0);
                    $this->userMessage->addInfo($this->trans('messages.site_registered'));
                    return $this->redirect('/owner/account');
                }
            } catch (\Exception $e) {
                $app['logger']->addError($e);
                $this->userMessage->addError($this->trans('errors.error'));
                return $this->redirect('/owner/dashboard');
            }
        });

        $controllers->post('/owner/site/prepare_icon', function(Request $request) use ($app) {
            $file_bag = $request->files;
            $image = $file_bag->get('icon');
            $path = PUBLIC_ROOT . '/siteicons/';
            $size = $image->getClientSize();
            if ( $size > (1024 * 100)) {
                return new JsonResponse(array('result'=>'failed', 'reason' => 'sizeover'));
            }
            $name = uniqid() . uniqid();
            $ext = $image->guessExtension();
            $fileName = $name . '.' . $ext;
            $image->move( $path, $fileName);

            $owner = $app['owner'];
            $cacheKey = 'prepare_icon/' . $owner->id;
            $app['redis']->setex($cacheKey, 60*60, $fileName);
            return new JsonResponse(array(
                'result' => 'success',
                'name' => $fileName
            ));
        });

        $controllers->get('/owner/site/fix_icon', function(Request $request) use ($app) {
            $siteId = $request->get('site_id');
            $owner = $app['owner'];
            $cacheKey = 'prepare_icon/' . $owner->id;
            $fileName = $app['redis']->get($cacheKey);
            $app['repository']->site->updateIcon($siteId, $fileName);
            return $this->redirect('/owner/settings');
        });

        $controllers->post('/owner/site/prepare_badge', function(Request $request) use ($app) {
            $file_bag = $request->files;
            $image = $file_bag->get('icon');
            $path = PUBLIC_ROOT . '/siteicons/';
            $size = $image->getClientSize();
            if ( $size > (1024 * 100)) {
                return new JsonResponse(array('result'=>'failed', 'reason' => 'sizeover'));
            }
            $name = uniqid() . uniqid();
            $ext = $image->guessExtension();
            $fileName = $name . '.' . $ext;
            $image->move( $path, $fileName);

            $owner = $app['owner'];
            $cacheKey = 'prepare_badge/' . $owner->id;
            $app['redis']->setex($cacheKey, 60*60, $fileName);
            return new JsonResponse(array(
                'result' => 'success',
                'name' => $fileName
            ));
        });

        $controllers->get('/owner/site/fix_badge', function(Request $request) use ($app) {
            $siteId = $request->get('site_id');
            $owner = $app['owner'];
            $cacheKey = 'prepare_badge/' . $owner->id;
            $fileName = $app['redis']->get($cacheKey);
            $app['repository']->site->updateBadge($siteId, $fileName);
            return $this->redirect('/owner/settings');
        });

        $controllers->get('/owner/site/delete', function(Request $request) use ($app) {
            $this->checkCsrf($request);

            $siteId = $request->get('site_id');
            $site = $app['repository']->site->find($siteId);

            if ( $app['owner']->id != $site['owner_id'] ) {
                $this->userMessage->addError($this->trans('errors.operation_is_not_allowed'));
                return $this->redirect('/owner/settings');
            }

            $this->userMessage->addInfo($this->trans('messages.site_is_deleted'));
            $app['repository']->site->delete($siteId);

            return $this->redirect('/owner/account');
        });


        $controllers->get('/owner/notification/delete', function(Request $request) use ($app) {
            $this->checkCsrf($request);

            $notificationId = $request->get('notification_id');
            $notification = $app['repository']->notification->find($notificationId);

            $owner = $app['owner'];
            $site = $app['repository']->site->find($notification->site_id);
            if ( $owner->id != $site->owner_id ) {
                $this->userMessage->addError($this->trans('errors.invalid_input'));
                return $this->redirect('/owner/notifications');
            }

            $app['repository']->notification->delete($notificationId);

            return $this->redirect('/owner/notifications');
        });

        $controllers->get('/owner/notification/send', function(Request $request) use ($app) {
            $this->checkCsrf($request);

            $notificationId = $request->get('notification_id');
            $notification = $app['repository']->notification->find($notificationId);
            if ( !$notification ) {
                $this->userMessage->addError($this->trans('errors.invalid_input'));
                return $this->redirect('/owner/notifications');
            }

            $owner = $app['owner'];
            $site = $app['repository']->site->find($notification->site_id);
            if ( $owner->id != $site->owner_id ) {
                $this->userMessage->addError($this->trans('errors.invalid_input'));
                return $this->redirect('/owner/notifications');
            }

            if ( !$owner->canSending($site) ) {
                $this->userMessage->addError($this->trans('errors.leach_to_max_send_messages'));
                return $this->redirect('/owner/notifications');
            }

            try {
                $notification->send();
                $this->userMessage->addInfo($this->trans('messages.start_sending'));
            } catch ( \Exception $e ) {
                $app['logger']->addError($e);
                $this->userMessage->addError($this->trans('errors.error'));
                return $this->redirect('/owner/notifications');
            }

            return $this->redirect('/owner/notifications');
        });

        $controllers->post('/owner/notification/post', function(Request $request) use ($app) {
            $this->checkCsrf($request);

            $notificationId = $request->get('notification_id');
            $siteId = $request->get('site_id');
            $subject = $request->get('subject');
            $linkUrl = $request->get('link_url');
            $content = $request->get('content');
            $scheduledAt = $request->get('scheduled_at');

            if ( strlen(trim($subject)) == 0 || strlen(trim($linkUrl)) == 0 || strlen(trim($content)) == 0 ) {
                $this->userMessage->addError($this->trans('errors.invalid_input'));
                return $this->redirect('/owner/notifications');
            }

            $site = $app['repository']->site->find($siteId);
            $owner = $app['owner'];
            if ( $site->owner_id != $owner->id ) {
                $this->userMessage->addError($this->trans('errors.invalid_input'));
                return $this->redirect('/owner/notifications');
            }

            if ( $notificationId ) {
                $notification = $app['repository']->notification->find($notificationId);
                if ( $notification['site_id'] != $site->id ) {
                    $this->userMessage->addError($this->trans('errors.invalid_input'));
                    return $this->redirect('/owner/notifications');
                }
            }

            try {
                if ( $notificationId ) {
                    $app['repository']->notification->update($notificationId, $subject, $content, $linkUrl, $scheduledAt);
                } else {
                    $app['repository']->notification->create($siteId, $subject, $content, $linkUrl, null, $scheduledAt);
                }
                $app['repository']->site->removeSiteJsonCache($siteId, '');
            } catch (\Exception $e) {
                $app['logger']->addError($e);
                $this->userMessage->addError($this->trans('errors.error'));
            }
            return $this->redirect('/owner/notifications');
        });

        $controllers->get('/owner/direct-embedded-zip', function(Request $request) use ($app) {
            $siteId = $request->get('site_id');
            $site = $app['repository']->site->find($siteId);
            $zip = new \ZipArchive();
            $filename = tempnam(sys_get_temp_dir(), 'bpush_');
            $rootDir = 'direct-embedded';
            if ( $zip->open($filename, \ZipArchive::CREATE) ) {
                $zip->addEmptyDir($rootDir);
                $rootDir .= DIRECTORY_SEPARATOR;
                $jsGen = new JsGenerator($app);
                $serviceWorkerJs = $jsGen->generateServiceWorker($site);

                $loaderJs = $this->render('embedded/loader.js.twig', [
                    'endpoint_base' => 'https://' . DOMAIN_NAME . ROOT_PATH,
                    'swlib_url' => '/swlib.js',
                    'app_key' => $site['app_key'],
                ]);
                $bpushHtml = $this->render('embedded/bpush.twig', [
                    'endpoint_base' => 'https://' . DOMAIN_NAME . ROOT_PATH,
                    'swlib_url' => '/swlib.js',
                    'app_key' => $site['app_key'],
                    'vapid_public_key' => $app['vapid']['public_key_hex'],
                    'simple_embedded' => false
                ]);
                $google_project_number = null;
                if ( defined('GOOGLE_PROJECT_NUMBER') ) {
                  $google_project_number = GOOGLE_PROJECT_NUMBER;
                }
                $manifestJson = $this->render('embedded/manifest.json.twig', [
                    'GOOGLE_PROJECT_NUMBER' => $google_project_number
                ]);
                $zip->addFromString($rootDir . "bpush/worker_{$site['app_key']}.js", $serviceWorkerJs);
                $zip->addFromString($rootDir . "bpush_loader.js", $loaderJs);
                $zip->addFromString($rootDir . "bpush/loader_{$site['app_key']}.html", $bpushHtml);
                $zip->addFromString($rootDir . "bpush/manifest_{$site['app_key']}.json", $manifestJson);
                $zip->addFile(PUBLIC_ROOT . '/js/swlib.js', $rootDir . "bpush/swlib_{$site['app_key']}.js");
                $zip->close();
                $zipContent = file_get_contents($filename);
                unlink($filename);

                return new Response(
                    $zipContent, 200, [
                        'Content-Type' => 'application/zip',
                        'Content-Disposition' => 'attachment; filename=direct-embedded.zip'
                    ]
                );
            } else {
                $this->userMessage->addError($this->trans('errors.error'));
                return $this->redirect('/owner/settings');
            }
        });

        $controllers->post('/owner/generate_api_token', function(Request $request) use ($app) {
            $ownerToken = $app['repository']->ownerToken->create($app['owner']->id);

            return new JsonResponse(['api_token'=>$ownerToken['api_token']]);
        });

        $controllers->post('/owner/update_rss', function(Request $request) use ($app) {
            $this->checkCsrf($request);

            $site = $this->getSelectedSite($app);
            $useRss = $request->get('use_rss');
            $feedUrl = $request->get('feed_url');

            $app['repository']->site->updateUseRss($site->id, $useRss);
            $app['repository']->siteRss->createOrReplace($site->id, $feedUrl);

            return $this->redirect('/owner/settings');
        });

        return $controllers;
    }

    public function getSelectedSiteId(Request $request) {
        $siteId = $request->get('site_id');
        if ( empty($siteId) ) {
            if ( isset($_SESSION['selected_site_id']) ) {
                $siteId = $_SESSION['selected_site_id'];
            }
        }
        return $siteId;
    }

    public function getSelectedSite(Application $app) {
        if ( isset($_SESSION['selected_site_id']) ) {
            $siteId = $_SESSION['selected_site_id'];
            $site = $app['repository']->site->find($siteId);
            if ( $site['owner_id'] != $app['owner']->id ) {
                return null;
            }
            return $site;
        }
        return null;
    }

    private function getSites(Application $app, $siteId = false) {
        $owner = $app['owner'];
        $sites = $app['repository']->site->findByOwnerId($owner->id);
        $sites = ipull($sites, null, 'id');
        $selectedSite = null;
        if ( count($sites) == 1 ) {
            $arr = array_values($sites);
            $selectedSite = array_shift($arr);
        } else if ( count($sites) > 1 ) {
            if ( $siteId && isset($sites[$siteId]) ) {
                $selectedSite = $sites[$siteId];
            } else {
                if ( isset($_SESSION['selected_site_id']) ) {
                    $siteId = $_SESSION['selected_site_id'];
                    $selectedSite = $sites[$siteId];
                }
            }
        }
        return array($sites, $selectedSite);
    }

}

