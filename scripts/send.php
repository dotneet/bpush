<?php
/**
 * send an notification scheduled by an owner.
 */

set_time_limit(0);

require_once __DIR__ . '/../src/init.php';

$notifications = $app['repository']->notification->findReady();
try {
    foreach ( $notifications as $notification ) {
        $notification->send();
    }
} catch ( \Exception $e ) {
    $app['logger']->addError($e);
}

