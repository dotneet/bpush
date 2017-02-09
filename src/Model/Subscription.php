<?php
namespace BPush\Model;

class Subscription implements \ArrayAccess
{
    use DatabaseRecord;

    public function __construct($app, array $data)
    {
        $this->app = $app;
        $this->setAsProperty($data);
    }

}

