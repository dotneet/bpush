<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Minishlink\WebPush\VAPID;

$file = __DIR__ . '/../vapid_keys.php';
$keys = VAPID::createVapidKeys();
file_put_contents($file, <<<EOT
<?php
function getVapidKeys() {
  return array(
    'public_key' => "{$keys['publicKey']}",
    'private_key' => "{$keys['privateKey']}"
  );
}
EOT
);

