<?php
namespace BPush\Model;

use Ramsey\Uuid\Uuid;

class SiteRepository 
{
    use Repository;
    protected $transformClassName = 'Site';

    protected $db;

    /** @var \Silex\Application */
    private $app;

    /** @var \Predis\Client */
    private $redis;

    public function __construct($app)
    {
        $this->app = $app;
        $this->db = $app['db'];
    }

    public function count()
    {
        $sql = 'SELECT COUNT(*) FROM sites';
        return $this->db->fetchColumn($sql);
    }

    public function find($id)
    {
        $sql = 'SELECT * FROM sites WHERE id = ? AND remove_at IS NULL';
        $row = $this->db->fetchAssoc($sql, array($id));
        return $this->transform($row);
    }

    public function findByAppKey($appKey)
    {
        $sql = 'SELECT * FROM sites WHERE app_key = ? AND remove_at IS NULL';
        $row = $this->db->fetchAssoc($sql, array($appKey));
        return $this->transform($row);
    }

    public function findByOwnerId($ownerId)
    {
        $sql = 'SELECT * FROM sites WHERE owner_id = ? AND remove_at IS NULL';
        $rows = $this->db->fetchAll($sql, array($ownerId));
        return $this->transformRows($rows);
    }

    public function create($ownerId, $siteName, $siteUrl, $useListPage = 0)
    {
        $appKey = Uuid::uuid4();
        $sql = 'INSERT INTO sites(owner_id,app_key,name,url,use_list_page, created) VALUES(?, ?, ?, ?, ?, ?)';
        $this->db->executeUpdate($sql, array($ownerId, $appKey->toString(), $siteName, $siteUrl, $useListPage, strftime('%F %T')));
        $id = $this->db->lastInsertId();
        return $this->find($id);
    }

    public function update($siteId, $siteName, $siteUrl, $useListPage)
    {
        $sql = 'UPDATE sites SET name = ?, url = ?, use_list_page = ? WHERE id = ?';
        return $this->db->executeUpdate($sql, array($siteName, $siteUrl, (int)$useListPage, $siteId));
    }

    public function updateUseRss($siteId, $useRss)
    {
        $sql = 'UPDATE sites SET use_rss = ? WHERE id = ?';
        return $this->db->executeUpdate($sql, array((int)$useRss, $siteId));
    }

    public function updateIcon($siteId, $fileName)
    {
        $sql = 'UPDATE sites SET icon = ? WHERE id = ?';
        return $this->db->executeUpdate($sql, array($fileName, $siteId));
    }

    public function updateBadge($siteId, $fileName)
    {
        $sql = 'UPDATE sites SET badge = ? WHERE id = ?';
        return $this->db->executeUpdate($sql, array($fileName, $siteId));
    }

    public function delete($siteId)
    {
        $sql = 'UPDATE sites SET remove_at = ? WHERE id = ?';
        return $this->db->executeUpdate($sql, array(strftime('%F %T'), $siteId));
    }

    public function getSiteJsonCache($appKey, $nid) {
        $cacheKey = 'site/json/' . $appKey . '/' . $nid;
        $cache = $this->app['redis']->get($cacheKey);
        if ( $cache ) {
            return json_decode($cache,true);
        }
        return false;
    }

    public function setSiteJsonCache($appKey, $nid, $data) {
        $cacheKey = 'site/json/' . $appKey . '/' . $nid;
        $this->app['redis']->setex($cacheKey, 60, json_encode($data));
    }

    public function removeSiteJsonCache($appKey, $nid = '') {
        $cacheKey = 'site/json/' . $appKey . '/' . $nid;
        return $this->app['redis']->del($cacheKey);
    }

}
