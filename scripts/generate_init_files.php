<?php

require_once __DIR__ . '/../src/init.php';

$content = $app['twig']->render('init/button.js.twig',array(
  'DOMAIN_NAME' => DOMAIN_NAME,
  'ROOT_PATH' => ROOT_PATH
));
if ( file_put_contents(PROJECT_ROOT . '/public/connect/button.js', $content) === false ) {
  fputs(STDERR, 'failed to write "/public/connect/button.js". please check a permission.');
  exit(1);
}

$google_project_number = null;
if ( defined('GOOGLE_PROJECT_NUMBER') ) {
  $google_project_number = GOOGLE_PROJECT_NUMBER;
}
$content = $app['twig']->render('init/manifest.json.twig',array(
  'GOOGLE_PROJECT_NUMBER' => $google_project_number,
  'ROOT_PATH' => ROOT_PATH
));
if ( file_put_contents(PROJECT_ROOT . '/public/manifest.json', $content) === false ) {
  fputs(STDERR, 'failed to write "/public/manifest.json". please check a permission.');
  exit(2);
}

$keyfile = PROJECT_ROOT .  '/vapid_keys.php';

if ( file_exists($keyfile) ) {
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

exit(0);

