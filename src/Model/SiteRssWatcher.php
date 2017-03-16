<?php
namespace BPush\Model;

use BPush\Model\RSS;

class SiteRssWatcher
{
    /** @var \Silex\Application */
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * register new rss item as notification.
     */
    public function sendNewItems()
    {
        $siteRssArray = $this->app['repository']->siteRss->findAllEnabled();
        foreach ( $siteRssArray as $siteRss ) {
            try {
                $newItem = $siteRss->findNewItem();
                if ( $newItem ) {
                    $site = $this->app['repository']->site->find($siteRss->site_id);
                    if ( empty($site) ) {
                        $this->app['repository']->siteRss->deleteBySiteId($siteRss->site_id);
                        continue;
                    }
                    $notification = $this->app['repository']->notification->create($site->id, $site->name, $newItem['title'], $newItem['url'], null);
                    $this->app['repository']->siteRss->updateLastModified($site->id, strtotime($newItem['date']));
                    Notifier::addSendNotificationCommand($this->app, $notification->id);
                }
            } catch ( \Exception $e ) {
                $this->app['logger']->addWarning('rss processing:' . $e->getMessage());
            }
        }
    }
}

