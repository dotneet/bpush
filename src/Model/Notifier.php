<?php
namespace BPush\Model;

class Notifier
{
    const COMMAND_QUEUE_KEY = 'notifier_command_queue';

    const COMMAND_SEND_NOTIFICATION = 'send_notification';

    /** @var \Silex\Application */
    private $app;

    /** @var \Predis\Client */
    private $redis;

    /** @var \BPush\Model\RepositoryFactory */
    private $repos;

    public function __construct($app)
    {
        $this->app = $app;
        $this->redis = $app['redis'];
        $this->repos = $app['repository'];
    }

    public static function addSendNotificationCommand($app, $notificationId, $filter = array())
    {
        $app['redis']->rpush(self::COMMAND_QUEUE_KEY, json_encode([
            'name' => self::COMMAND_SEND_NOTIFICATION,
            'notification_id' => $notificationId,
            'filter' => $filter
            ]));
    }

    public function start($time = null)
    {
        $lastDbConnecitonTime = time();
        while ( $time === null || $time ) {
            $command = $this->redis->blpop(self::COMMAND_QUEUE_KEY, 5);

            // for avoiding a connection timeout, reconnect every minutes.
            $now = time();
            if ( $lastDbConnecitonTime + 60  < $now ) {
                $this->app['db']->close();
                $this->app['db']->connect();
                $lastDbConnecitonTime = $now;
            }

            // flush received count and redirect count to a database.
            $this->app['repository']->notification->flushCountBuffer();

            if ( !$command or !$command[1] ) {
                continue;
            }
            $time = $time - 1;
            $command = json_decode($command[1], true);
            if ( $command ) {
                $this->executeCommand($command);
            }
        }
    }

    private function executeCommand($command)
    {
        switch ( $command['name'] ) {
        case self::COMMAND_SEND_NOTIFICATION:
        {
            $notificationId = $command['notification_id'];
            $notification = $this->repos->notification->find($notificationId);
            if ( $notification->sent_at == null ) {
                $result = $notification->sendImmediate($command['filter']);
                $this->app['logger']->addInfo('send Immediate:' . $result);
            }
            break;
        }
        default:
            $this->app['logger']->addWarning('Unknown command:' . $command['name']);
        }

    }

}


