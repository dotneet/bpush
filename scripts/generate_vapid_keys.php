<?php

set_include_path(get_include_path() . ':' . __DIR__ . '/../src');
set_include_path(get_include_path() . ':' . __DIR__ . '/../lib');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use Minishlink\WebPush\VAPID;

$keys = VAPID::createVapidKeys();
file_put_contents(PROJECT_ROOT .  '/vapid_keys.php', <<<EOT
<?php
function getVapidKeys() {
  return array(
    'public_key' => "{$keys['publicKey']}",
    'private_key' => "{$keys['privateKey']}"
  );
}
EOT
);

