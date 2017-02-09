<?php
namespace BPush\Model;

class Notification implements \ArrayAccess
{
    /** OK */
    const FAILURE_REASON_NONE = 0;
    /** @deprecated */
    const FAILURE_REASON_MAX_SEND_COUNT = 1;
    /** failed to send because of over capacity in the month. */
    const FAILURE_REASON_OVER_SENT_AMOUNT = 2;

    use DatabaseRecord;

    private $site = null;

    public function __construct($app, array $data)
    {
        $this->app = $app;
        foreach ( $data as $k => $v ) {
            $this->{$k} = $v;
        }
    }

    public function getSite()
    {
        if ( !isset($this->site) ) {
            $this->site = $this->app['repository']->site->find($this->site_id);
        }
        return $this->site;
    }

    public function canEdit()
    {
        return $this->sent_at == null;
    }

    /**
     * add command to queue for sending notifications.
     */
    public function send($filter = array())
    {
        Notifier::addSendNotificationCommand($this->app, $this->id, $filter);
    }

    /**
     * Send a notification immediately not via a command queue.
     *
     * @param $filter use to filter subscriptions.
     */
    public function sendImmediate(array $filter)
    {
        $this->app['logger']->addInfo('start to send a notification. id=' . $this->id);

        $site = $this->getSite();
        if ( $site == null ) {
            $this->app['logger']->addError('$site is null. site_id=' . $this->site_id);
            return false;
        }

        $owner = $this->app['repository']->owner->find($site->owner_id);

        if ( !$owner->canSending($site) ) {
            $this->app['logger']->addInfo('stop sending. id=' . $this->id . ' reason=' . self::FAILURE_REASON_OVER_SENT_AMOUNT);
            $this->app['repository']->notification->updateFailureReason($this->id, self::FAILURE_REASON_OVER_SENT_AMOUNT);
            return false;
        }
        
        if ( empty($filter) ) {
            $subscriptions = $site->getSubscriptions();
        } else {
            $subscriptions = $site->getSubscriptionsByTags($filter['tags']);
        }
        $this->app['repository']->notification->updateSentAt($this->id);

        $push = new PushMessage($this->app);
        if ( USE_VAPID_PROTOCOL ) {
            $push->send($site, $this, $subscriptions);
        } else {
            $arns = ipull($subscriptions, 'endpoint_arn');
            $push->publish($this->content, $arns);
        }

        $targetCount = count($subscriptions);
        $this->app['repository']->sendLog->create($site->id, $targetCount);

        $this->app['logger']->addInfo('finish sending. id=' . $this->id . ' target-count=' . $targetCount);

        return $targetCount;
    }

    public function increaseReceivedCount()
    {
        $this->app['repository']->notification->increaseReceivedCount($this->id);
    }

    public function increaseJumpCount()
    {
        $this->app['repository']->notification->increaseJumpCount($this->id);
    }
}

