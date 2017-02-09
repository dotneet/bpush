<?php
namespace BPush\Model;

class Owner implements \ArrayAccess
{
    use DatabaseRecord;

    const STATUS_UNCONFIRM = 1;
    const STATUS_CONFIRMED = 2;
    const STATUS_WITHDREW = 3;

    const GRADE_FREE = 10;
    const GRADE_CHARGED = 20;
    const GRADE_CUSTOM = 30;

    public function __construct($app, array $data)
    {
        $this->app = $app;
        $this->setAsProperty($data);
    }

    public static function validateMail($mail)
    {
        return preg_match('/^([a-zA-Z0-9._+-])+@([a-zA-Z0-9._-])+$/', $mail);
    }

    public static function validatePassword($password)
    {
        return preg_match('/^[a-zA-Z0-9_\\-@%]{8,16}$/', $password);
    }

    public function canLogin()
    {
        return $this->status == self::STATUS_CONFIRMED && $this->suspended == null;
    }

    public function verifyPassword($plainPassword)
    {
        return password_verify($plainPassword, $this->password);
    }

    public static function makePasswordHash($plainPassword)
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public function updatePassword($password)
    {
        if ( $this->app['repository']->owner->updatePassword($this->id, $password) ) {
            $this->password = $password;
        }
    }

    public function confirm()
    {
        $this->app['repository']->owner->confirm($this->id);
    }

    /** returns true if not over capacinty in the month. */
    public function canSendingAmountOfMessages($totalMessagesInMonth)
    {
        $gradeLimits = [
            self::GRADE_FREE => 50000,
            self::GRADE_CHARGED => 1000000,
            self::GRADE_CUSTOM => 10000000
        ]; 
        return $totalMessagesInMonth <= $gradeLimits[$this->grade];
    }


    /** returns amount of messages sent in the month. */
    public function getAmountOfSentMessagesInMonth($month = null) {
        if ( $month == null ) {
            $month = date('Y-m');
        }
        $sites = $this->app['repository']->site->findByOwnerId($this->id);
        $siteIds = array_map(function($s){return $s->id;}, $sites);
        return $this->app['repository']->sendLog->getTotalAmountOfSentMessagesInMonth($siteIds, $month);
    }

    /**
     * returns true, if capacity will be not over the limit after sending.
     */
    public function canSending($site, $month = null)
    {
        if ( !ENABLE_LIMIT_OF_NOTIFICATION_AMOUNT ) {
            return true;
        }

        $targetAmount = $this->app['repository']->subscription->countBySiteId($site->id);
        $sentAmount = $this->getAmountOfSentMessagesInMonth($month);
        $newAmount = $targetAmount + $sentAmount;
        return $this->canSendingAmountOfMessages($newAmount);
    }

}

