<?php
/**
 * add user manually.
 *
 * this command doesn't require an email confirmation.
 *
 * USAGE:
 * php scripts/add_user.php <email> <password>
 */

require_once __DIR__ . '/../src/init.php';

if ( count($argv) != 3 ) {
  fputs(STDERR, "please input two arguments. <email> <password>\n");
  exit(1);
}
$email = $argv[1];
$password = $argv[2];

try {
    $user = $app['repository']->owner->create($email, $password);
    $app['repository']->owner->confirm($user->id);
} catch (\Exception $e) {
    $app['logger']->addError($e->getMessage());
    exit(1);
}

exit(0);

