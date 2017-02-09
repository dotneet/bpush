<?php
namespace BPush\Model;

/**
 * manage user messages.
 */
class UserMessage
{
    const TYPE_INFO = 1;
    const TYPE_WARN = 2;
    const TYPE_ERROR = 3;

    public function __construct($app, $userId)
    {
        $this->app = $app;
        $this->redis = $app['redis'];
        $this->ownerId = $userId;
    }

    private function makeUserMessageKey($type)
    {
        return "UserMessage/Owner/{$this->ownerId}/${type}";
    }

    public function addInfo($msg)
    {
        $this->redis->rpush($this->makeUserMessageKey(self::TYPE_INFO), $msg);
    }

    public function pullInfos()
    {
        return $this->pullByType(self::TYPE_INFO);
    }

    public function addError($msg)
    {
        $this->redis->rpush($this->makeUserMessageKey(self::TYPE_ERROR), $msg);
    }

    public function pullErrors()
    {
        return $this->pullByType(self::TYPE_ERROR);
    }

    public function pullByType($type)
    {
        $key = $this->makeUserMessageKey($type);
        $len = $this->redis->llen($key);
        if ( $len == 0 ) {
            return array();
        }
        $result = $this->redis->lrange($key, 0, $len);
        $this->redis->del($key);
        return $result;
    }

}

