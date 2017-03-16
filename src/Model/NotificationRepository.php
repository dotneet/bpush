<?php
namespace BPush\Model;

class NotificationRepository 
{
    use Repository;
    protected $transformClassName = 'Notification';

    protected $db;

    /** @var \Silex\Application */
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
        $this->db = $app['db'];
    }

    public function count()
    {
        $sql = 'SELECT COUNT(*) FROM notifications';
        return $this->db->fetchColumn($sql);
    }

    public function find($id)
    {
        $sql = 'SELECT * FROM notifications WHERE id = ?';
        $row = $this->db->fetchAssoc($sql, array($id));
        return $this->transform($row);
    }

    public function findSentItemsBySiteId($siteId, $limit = 10)
    {
        $limit = (int)$limit;
        $sql = 'SELECT * FROM notifications '
            .  ' WHERE site_id = ? AND sent_at IS NOT NULL AND visible = 1'
            .  ' ORDER BY created DESC'
            .  ' LIMIT ' . $limit;
        $rows = $this->db->fetchAll($sql, array($siteId));
        return $this->transformRows($rows);
    }

    public function findLastSentItem($siteId)
    {
        $sql = 'SELECT * FROM notifications'
            .  ' WHERE site_id = ? AND sent_at IS NOT NULL'
            .  ' ORDER BY sent_at DESC LIMIT 1';
        $row = $this->db->fetchAssoc($sql, array($siteId));
        return $this->transform($row);
    }

    public function findBySiteId($siteId, $offset = 0, $limit = 10)
    {
        $offset = (integer)$offset;
        $limit = (integer)$limit;
        $sql = 'SELECT * FROM notifications '
            .  ' WHERE site_id = ? AND visible = 1 '
            .  ' ORDER BY created DESC'
            .  " LIMIT $offset, $limit"
            ;
        $rows = $this->db->fetchAll($sql, array($siteId));
        return $this->transformRows($rows);
    }

    /**
     * find items that ready for sending.
     */
    public function findReady()
    {
        $sql = 'SELECT * FROM notifications WHERE sent_at IS NULL AND failure_reason = 0 AND scheduled_at < ?';
        $rows = $this->db->fetchAll($sql, array(strftime('%F %T')));
        return $this->transformRows($rows);
    }

    public function countBySiteId($siteId) {
        $sql = 'SELECT COUNT(*) FROM notifications '
            .  ' WHERE site_id = ? '
            .  ' ORDER BY created DESC';
        return $this->db->fetchColumn($sql, array($siteId));
    }

    public function countByDay($siteId, $start, $end) {
        $sql = 'SELECT DATE_FORMAT(created, "%Y-%m-%d") AS day, COUNT(*) AS sending_count, SUM(received_count) AS total_receive_count, SUM(jump_count) AS total_jump_count ' 
               . ' FROM notifications '
               . ' WHERE created BETWEEN ? AND ?'
               . ' GROUP BY day '
               . ' ORDER BY day ASC';
        return $this->db->fetchAll($sql, [$start->format('c'), $end->format('c')]);
    }

    public function create($siteId, $subject, $content, $postUrl, $imageUrl, $scheduledAt, $visible=true)
    {
        $scheduledAt = $scheduledAt ? $scheduledAt : null;
        $sql = 'INSERT INTO notifications(site_id,subject,content,post_url,image_url,scheduled_at,visible,created) VALUES(?, ?, ?, ?, ?, ?, ?,?)';
        $this->db->executeUpdate($sql, array($siteId, $subject, $content, $postUrl, $imageUrl, $scheduledAt, $visible ? 1 : 0,strftime('%F %T')));
        $id = $this->db->lastInsertId();
        return $this->find($id);
    }

    public function delete($notificationId)
    {
        $sql = 'DELETE FROM notifications WHERE id = ?';
        return $this->db->executeUpdate($sql, array($notificationId));
    }

    public function update($notificationId, $subject, $content, $postUrl, $imageUrl, $scheduledAt)
    {
        $scheduledAt = $scheduledAt ? $scheduledAt : null;
        $sql = 'UPDATE notifications SET subject = ?, content = ?, post_url = ?, image_url = ?, scheduled_at = ? WHERE id = ?';
        return $this->db->executeUpdate($sql, array($subject, $content, $postUrl, $iamgeUrl, $scheduledAt, $notificationId));
    }

    public function updateScheduledAt($notificationId, $time)
    {
        $sql = 'UPDATE notifications SET failure_reason = ?, scheduled_at = ? WHERE id = ?';
        $this->db->executeUpdate($sql, array(Notification::FAILURE_REASON_NONE, strftime('%F %T',$time), $notificationId));
    }

    public function updateSentAt($notificationId)
    {
        $sql = 'UPDATE notifications SET sent_at = ? WHERE id = ?';
        $this->db->executeUpdate($sql, array(strftime('%F %T'), $notificationId));
    }

    public function updateFailureReason($notificationId, $reason)
    {
        $sql = 'UPDATE notifications SET failure_reason = ? WHERE id = ?';
        $this->db->executeUpdate($sql, [$reason, $notificationId]);
    }

    /**
     * use redis for reducing mysql accesses.
     */
    const INCREASE_NOTIFICATION_SET_KEY = 'Notification/IncreaseBufferSet';
    const INCREASE_RECEIVED_COUNT_BUFFER_KEY = 'Notification/IncreaseReceivedCountBuffer/';
    const INCREASE_JUMP_COUNT_BUFFER_KEY = 'Notification/IncreaseJumpCountBuffer/';
    public function increaseReceivedCountBuffer($notificationId) {
        if ( $notificationId ) {
            $this->app['redis']->incr(self::INCREASE_RECEIVED_COUNT_BUFFER_KEY . $notificationId);
            $this->app['redis']->sadd(self::INCREASE_NOTIFICATION_SET_KEY, $notificationId);
        }
    }
    public function increaseJumpCountBuffer($notificationId) {
        if ( $notificationId ) {
            $this->app['redis']->incr(self::INCREASE_JUMP_COUNT_BUFFER_KEY . $notificationId);
            $this->app['redis']->sadd(self::INCREASE_NOTIFICATION_SET_KEY, $notificationId);
        }
    }

    /**
     * move count data stored in redis to database.
     */
    public function flushCountBuffer() {
        $notificationIds = $this->app['redis']->smembers(self::INCREASE_NOTIFICATION_SET_KEY);
        foreach ( $notificationIds as $nid ) {
            $key = self::INCREASE_RECEIVED_COUNT_BUFFER_KEY . $nid;
            $count = $this->app['redis']->get($key);
            if ( $count ) {
                $this->increaseReceivedCount($nid, $count);
                $this->app['redis']->del($key);
            }
            $key = self::INCREASE_JUMP_COUNT_BUFFER_KEY . $nid;
            $count = $this->app['redis']->get($key);
            if ( $count ) {
                $this->increaseJumpCount($nid, $count);
                $this->app['redis']->del($key);
            }
        }
    }
    
    public function increaseReceivedCount($notificationId, $addCount)
    {
        $sql = 'UPDATE notifications SET received_count = received_count + ? WHERE id = ?';
        $this->db->executeUpdate($sql, [$addCount, $notificationId]);
    }

    public function increaseJumpCount($notificationId, $addCount)
    {
        $sql = 'UPDATE notifications SET jump_count = jump_count + ? WHERE id = ?';
        $this->db->executeUpdate($sql, [$addCount, $notificationId]);
    }
}

