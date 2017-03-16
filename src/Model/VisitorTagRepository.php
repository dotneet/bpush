<?php
namespace BPush\Model;

class VisitorTagRepository 
{
    use Repository;
    protected $transformClassName = 'VisitorTag';

    protected $db;

    /** @var \Silex\Application */
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
        $this->db = $app['db'];
    }

    public function countBySiteId($siteId)
    {
        $sql = 'SELECT COUNT(*) FROM visitor_tags WHERE site_id = ?';
        return $this->db->fetchColumn($sql, [$siteId]);
    }

    public function find($siteId, $visitorId, $tag)
    {
        $sql = 'SELECT * FROM visitor_tags WHERE site_id = ? AND visitor_id = ? AND tag = ?';
        $row = $this->db->fetchAssoc($sql, [$siteId, $visitorId, $tag]);
        return $this->transform($row);
    }

    public function create($siteId, $visitorId, $tags)
    {
        $sql = 'INSERT IGNORE INTO visitor_tags(site_id,visitor_id,tag) '
            . ' VALUES(?, ?, ?) ';
        foreach ( $tags as $tag ) {
            $this->db->executeUpdate($sql, array($siteId, $visitorId, $tag));
        }
        return $this->find($siteId, $visitorId, $tag);
    }
}
