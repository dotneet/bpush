<?php
/**
 * Background process for sending notifications queued by online process.
 */
xhprof_enable(XHPROF_FLAGS_MEMORY);
register_shutdown_function(function() {
    echo "Shutdown";
    $data = xhprof_disable();
    $runs = new XHProfRuns_Default();
    $runs->save_run($data, 'APPLICATION_NAME');
});

require_once __DIR__ . '/../src/init.php';

$max_execution_count = null;

if (count($argv) > 0 ) {
  $max_execution_count = $argv[1];
}

try {
    $notifier = new \BPush\Model\Notifier($app);
    $notifier->start($max_execution_count);
} catch(\Exception $e) {
    $app['logger']->addError($e);
    exit(-1);
}

exit(0);


