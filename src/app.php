<?php

require __DIR__ . '/init.php';

session_name('sid_'. strtolower(BPUSH_ENVIRONMENT));
session_start();

$app->mount('/', new \BPush\Controller\HomeController());
$app->mount('/', new \BPush\Controller\AuthController());
$app->mount('/', new \BPush\Controller\OwnerController());
$app->mount('/', new \BPush\Controller\SiteController());
$app->mount('/', new \BPush\Controller\SiteApiController());
$app->mount('/admin/', new \BPush\Controller\AdminController());
$app->mount('/oapi/v1/', new \BPush\Controller\OwnerApiController());

