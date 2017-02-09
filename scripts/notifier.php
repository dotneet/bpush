<?php
/**
 * Background process for sending notifications queued by online process.
 */

require_once __DIR__ . '/../src/init.php';

try {
    $notifier = new \BPush\Model\Notifier($app);
    $notifier->start();
} catch(\Exception $e) {
    $app['logger']->addError($e);
    exit(-1);
}

exit(0);


