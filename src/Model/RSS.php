<?php
namespace BPush\Model;

use PicoFeed\Reader\Reader;

class RSS {
    public function __construct($app)
    {
        if ( !$app ) {
            throw new \Exception('you must be set $app.');
        }
        $this->app = $app;
    }

    public function getLastItem($url)
    {
        $reader = new Reader();
        $resource = $reader->download($url);
        $parser = $reader->getParser(
            $resource->getUrl(),
            $resource->getContent(),
            $resource->getEncoding()
        );
        $feed = $parser->execute();
        if ( count($feed->items) == 0 ) {
            return null;
        }

        $items = [];
        $dates = [];
        foreach ( $feed->items as $item ) {
            $items[] = $item;
            $dates[] = date('c', $item->getDate()->getTimestamp());
        }
        array_multisort($dates, SORT_DESC, SORT_STRING, $items);
        return [
            'date'  => $dates[0],
            'title' => $items[0]->getTitle(),
            'url'   => $items[0]->getUrl(),
        ];
    }
}


