<?php
/**
 * add an user manually.
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
    /** @var \BPush\Model\OwnerRepository $ownerRepository */
    $ownerRepository = $app['repository']->owner;

    if ( !\BPush\Model\Owner::validateMail($email) ) {
        throw new Exception('email format is invalid.');
    }
    if ( !\BPush\Model\Owner::validatePassword($password) ) {
        throw new Exception('password format is invalid.');
    }
    $user = $ownerRepository->create($email, $password);
    $ownerRepository->confirm($user->id);
} catch (\Exception $e) {
    fputs(STDERR, $e->getMessage() . "\n");
    $app['logger']->addError($e->getMessage());
    exit(1);
}

exit(0);

