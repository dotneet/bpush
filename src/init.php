<?php

set_include_path(get_include_path() . ':' . __DIR__ . '/../src');
set_include_path(get_include_path() . ':' . __DIR__ . '/../lib');
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vapid_keys.php';
require_once 'utils.php';
require_once 'dbutils.php';

use Silex\Provider\MonologServiceProvider;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Monolog\Formatter\LineFormatter;

date_default_timezone_set(DEFAULT_TIMEZONE);

function check_loaded_extensions() {
    $required_extensions = ['xml','intl','zip','mbstring','PDO','pdo_mysql','dom','curl','json','gmp'];
    $extensions = get_loaded_extensions();
    $result = [];
    foreach ( $required_extensions as $ext ) {
        if ( !in_array($ext, $extensions) ) {
            $result[] = $ext;
        }
    }
    return $result;
}

$extensions_not_found = check_loaded_extensions();
if ( !empty($extensions_not_found) ) {
    fputs(STDERR, "Please sure to install these extensions:\n");
    foreach ( $extensions_not_found as $ext ) {
        fputs(STDERR, " - $ext\n");
    }
    exit(1);
}

$app = new Silex\Application(isset($dependencies) ? $dependencies : array());

if ( BPUSH_ENVIRONMENT != 'PRODUCTION' ) {
    $app['debug'] = true;
}

$app->register(new \Silex\Provider\ServiceControllerServiceProvider());

$app->register(new \Silex\Provider\DoctrineServiceProvider(), array(
    'dbs.options' => array(
        'mysql_master' => array(
            'driver' => 'pdo_mysql',
            'host' => MYSQL_HOST,
            'dbname' => MYSQL_DB,
            'user' => MYSQL_USER,
            'password' => MYSQL_PASS,
            'charset' => 'utf8mb4'
        )
    )
));

$app['db'] = function () use ($app) {
    $mysql = $app['dbs']['mysql_master'];
    $mysql->query('SET NAMES utf8mb4');
    return $mysql;
}; 

$app['redis'] = function() use($app) {
  return new \Predis\Client(
      'tcp://' . REDIS_HOST . ':' . REDIS_PORT,
      ['prefix' => REDIS_PREFIX, 'cluster' => false]
    );
};

$app['repository'] = function() use ($app) {
    return new \BPush\Model\RepositoryFactory($app);
};

$app['vapid'] = function() use ($app) {
  $keys = getVapidKeys();
  $vapidPublicKeyBase64Safe = str_replace(array('+', '/', '='), array('_', '-', '.'), $keys['public_key']);
  $vapidPublicKeyBinary = base64_decode($keys['public_key']);
  $vapidPublicKeyHex = '0x' . implode(',0x',str_split(bin2hex($vapidPublicKeyBinary),2));
  return array(
    'public_key_base64' => $keys['public_key'],
    'private_key_base64' => $keys['private_key'],
    'public_key_base64_urlsafe' => $vapidPublicKeyBase64Safe,
    'public_key_hex' => $vapidPublicKeyHex
  );
};

$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/app.log',
    'monolog.name' => 'bpush.applog',
    'monolog.formatter' => new LineFormatter(null, null, true)
)); 

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/View',
    'twig.options' => [
        'strict_variables' => true,
        'cache' => TWIG_CACHE
    ]
)); 

$app->register(new FormServiceProvider());

$app->register(new Silex\Provider\LocaleServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale' => DEFAULT_LOCALE,
    'locale_fallbacks' => array('ja','en')
));
$app->extend('translator', function($translator, $app) {
    $translator->addLoader('yaml', new YamlFileLoader());
    $translator->addResource('yaml', __DIR__.'/../locales/en.yml', 'en');
    $translator->addResource('yaml', __DIR__.'/../locales/ja.yml', 'ja');
    return $translator;
});
