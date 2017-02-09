<?php
/**
 * find new rss items and register it as an notification.
 */

require_once __DIR__ . '/../src/init.php';

use PicoFeed\Reader\Reader;
use BPush\Model\RSS;
use BPush\Model\SiteRss;
use BPush\Model\SiteRssWatcher;

try {
    $watcher = new SiteRssWatcher($app);
    $watcher->sendNewItems();
} catch (\Exception $e) {
    $app['logger']->addError($e->getMessage());
    exit(1);
}

exit(0);

