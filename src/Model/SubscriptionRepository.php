<?php
namespace BPush\Model;

use Ramsey\Uuid\Uuid;

class SubscriptionRepository 
{
    use Repository;
    protected $transformClassName = 'Subscription';

    protected $db;

    /** @var \Silex\Application */
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
        $this->db = $app['db'];
    }

    public function find($id)
    {
        $sql = 'SELECT * FROM subscriptions WHERE id = ?';
        $row = $this->db->fetchAssoc($sql, array($id));
        if ( $row ) {
            return $this->transform($row);
        }
        return null;
    }

    public function count()
    {
        $sql = 'SELECT COUNT(*) FROM subscriptions';
        return $this->db->fetchColumn($sql);
    }

    public function countBySiteId($siteId)
    {
        $sql = 'SELECT COUNT(*) FROM subscriptions WHERE site_id = ?';
        return $this->db->fetchColumn($sql , array($siteId));
    }

    public function findBySiteId($siteId)
    {
        $sql = 'SELECT * FROM subscriptions WHERE site_id = ?';
        $rows = $this->db->fetchAll($sql, array($siteId));
        return $this->transformRows($rows);
    }

    public function findByTags($siteId, $tags)
    {
        $sql = 'SELECT * FROM subscriptions s INNER JOIN visitor_tags v ON (s.visitor_id = v.visitor_id) '
              .'  WHERE s.site_id = ? AND v.site_id = ? AND ' . sql_in_clause('v.tag', $tags);
        $params = array_merge([$siteId, $siteId], $tags);
        $rows = $this->db->fetchAll($sql, $params);
        return $this->transformRows($rows);
    }

    public function create($siteId, $visitorId, $data, $subscriptionId, $ipAddr, $userAgent, $locale)
    {
        $push = new PushMessage($this->app);
        $endpoint = null;
        $auth_token = null;
        $p256dh = null;
        if ( isset($data['endpoint']) ) {
            $endpoint = $data['endpoint'];
        }
        if ( isset($data['keys']) ) {
            if ( isset($data['keys']['auth']) ) {
                $auth_token = $data['keys']['auth'];
            }
            if ( isset($data['keys']['p256dh']) ) {
                $p256dh = $data['keys']['p256dh'];
            }
        }
        if ( $visitorId == null || empty($visitorId) ) {
            $visitorId = Uuid::uuid4();
        }
        $arnEndpoint = null;
        if ( !USE_VAPID_PROTOCOL ) {
            $arnEndpoint = $push->registerGcm($subscriptionId);
        }
        $sql = 'INSERT INTO subscriptions(site_id,visitor_id,endpoint,auth_token,p256dh,subscription_id,endpoint_arn,ip_address,user_agent,locale,created) '
            .  ' VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) '
            .  ' ON DUPLICATE KEY UPDATE endpoint = VALUES(endpoint), auth_token = VALUES(auth_token), p256dh = VALUES(p256dh), endpoint_arn = VALUES(endpoint_arn), ip_address = VALUES(ip_address), user_agent = VALUES(user_agent), locale = VALUES(locale), created = VALUES(created)';
        $this->db->executeUpdate($sql, array($siteId, $visitorId, $endpoint, $auth_token, $p256dh, $subscriptionId, $arnEndpoint, $ipAddr, $userAgent, $locale, strftime('%F %T')));
        $id = $this->db->lastInsertId();
        return $this->find($id);
    }

    public function deleteBySubscriptionId($sid)
    {
        $sql = 'DELETE FROM subscriptions WHERE subscription_id = ?';
        return $this->db->executeUpdate($sql, array($sid));
    }

    public function deleteByEndpoint($endpoint)
    {
        $sql = 'DELETE FROM subscriptions WHERE endpoint = ?';
        return $this->db->executeUpdate($sql, array($endpoint));
    }

    public function deleteByEndpointArn($arn)
    {
        $sql = 'DELETE FROM subscriptions WHERE endpoint_arn = ?';
        return $this->db->executeUpdate($sql, array($arn));
    }

}
