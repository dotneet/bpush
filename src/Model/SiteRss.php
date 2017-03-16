<?php
namespace BPush\Model;

use BPush\Model\RSS;

class SiteRss implements \ArrayAccess
{
    use DatabaseRecord;

    /** @var \Silex\Application */
    private $app;

    public function __construct($app, array $data)
    {
        $this->app = $app;
        $this->setAsProperty($data);
    }

    /**
     * returns new rss items if found it.
     */
    public function findNewItem()
    {
        $rss = new RSS($this->app);
        try {
            $item = $rss->getLastItem($this->feed_url);
            $lastDate = strtotime($item['date']);
            $currentDate = strtotime($this->last_modified);
            if ( $lastDate > $currentDate ) {
                return $item;
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }
}

