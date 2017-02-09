<?php
namespace BPush\Model;

class Site implements \ArrayAccess
{
    use DatabaseRecord;

    public function __construct($app, array $data)
    {
        $this->app = $app;
        $this->setAsProperty($data);
    }

    public function getSubscriptions()
    {
        return $this->app['repository']->subscription->findBySiteId($this->id);
    }

    public function getSubscriptionsByTags($tags)
    {
        return $this->app['repository']->subscription->findByTags($this->id, $tags);
    }

    /**
     * get amount of notifications in the month.
     */
    public function getAmountOfSentMessagesInMonth($month = null)
    {
        if ( $month == null ) {
            $month = date('Y-m');
        }
        $sites = $this->app['repository']->site->findByOwnerId($this->owner_id);
        $siteIds = array_map(function($s){return $s->id;}, $sites);
        $amounts = $this->app['repository']->sendLog->getAmountOfSentMessagesInMonth($siteIds, $month);
        return isset($amounts[$this->id]) ? $amounts[$this->id] : 0;
    }

    public function getRss()
    {
        return $this->app['repository']->siteRss->findBySiteId($this->id);
    }
}

