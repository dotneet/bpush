<?php
namespace BPush\Model;

class SiteRssRepository 
{
    use Repository;
    protected $transformClassName = 'SiteRss';

    protected $db;

    public function __construct($app)
    {
        $this->app = $app;
        $this->db = $app['db'];
    }

    public function count()
    {
        $sql = 'SELECT COUNT(*) FROM site_rss';
        return $this->db->fetchColumn($sql);
    }

    public function findBySiteId($id)
    {
        $sql = 'SELECT * FROM site_rss WHERE site_id = ?';
        $row = $this->db->fetchAssoc($sql, array($id));
        return $this->transform($row);
    }

    public function findAllEnabled()
    {
        $sql = 'SELECT sr.* FROM site_rss sr INNER JOIN sites s ON (sr.site_id = s.id)'
              . ' WHERE '
              . '  sr.feed_url != "" AND s.use_rss = 1 ';
        $rows = $this->db->fetchAll($sql);
        return $this->transformRows($rows);
    }

    public function createOrReplace($siteId, $rssUrl)
    {
        $sql = 'INSERT INTO site_rss(site_id,feed_url,last_modified,created) '
            . ' VALUES(?, ?, NOW(), ?) '
            . ' ON DUPLICATE KEY UPDATE feed_url=VALUES(feed_url), last_modified = NOW()';
        $this->db->executeUpdate($sql, array($siteId, $rssUrl, strftime('%F %T')));
        return $this->findBySiteId($siteId);
    }

    public function updateLastModified($siteId, $lastModified)
    {
        $sql = 'UPDATE site_rss SET last_modified = ? WHERE site_id = ?';
        $this->db->executeUpdate($sql, array(strftime('%F %T', $lastModified), $siteId));
    }

    public function deleteBySiteId($siteId)
    {
        $sql = 'DELETE FROM site_rss WHERE site_id = ?';
        return $this->db->executeUpdate($sql, array($siteId));
    }

}
