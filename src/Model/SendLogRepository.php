<?php
namespace BPush\Model;

class SendLogRepository 
{
    protected $db;

    public function __construct($app)
    {
        $this->db = $app['db'];
    }

    public function countInTodayAdmin()
    {
        $startTime = strftime('%F 00:00:00');
        $endTime = strftime('%F 23:59:59');

        $ph = implode(',', array_fill(0,count($siteIds),'?'));
        $sql = 'SELECT COUNT(*) AS cnt '
            . ' FROM send_logs '
            . ' WHERE sent_at BETWEEN ? AND ?';

        return $this->db->fetchColumn($sql, array($startTime, $endTime));
    }

    public function countInToday(array $siteIds)
    {
        $startTime = strftime('%F 00:00:00');
        $endTime = strftime('%F 23:59:59');

        $ph = implode(',', array_fill(0,count($siteIds),'?'));
        $sql = 'SELECT site_id, COUNT(*) AS cnt '
            . ' FROM send_logs '
            . ' WHERE site_id IN (' . $ph . ') AND sent_at BETWEEN ? AND ?'
            . ' GROUP BY site_id';

        $params = array_merge($siteIds, array($startTime, $endTime));
        $rows = $this->db->fetchAll($sql, $params);
        return ipull($rows, 'cnt', 'site_id');
    }

    /**
     * get total amount of notifications in the month.
     */
    public function getTotalAmountOfSentMessagesInMonth(array $siteIds, $month)
    {
        $results = $this->getAmountOfSentMessagesInMonth($siteIds, $month);
        $sum = 0;
        foreach ( $results as $key => $val ) {
            $sum += $val;
        }
        return $sum;
    }

    /**
     * get amount of notifications in the month per site.
     */
    public function getAmountOfSentMessagesInMonth(array $siteIds, $month)
    {
        $startTime = "${month}-01 00:00:00";
        $endDateTime = new \DateTime("${month}-01 00:00:00");
        $endDateTime->add(new \DateInterval('P1M'));
        $endTime = strftime('%F %T', $endDateTime->getTimestamp());

        $ph = implode(',', array_fill(0,count($siteIds),'?'));
        $sql = 'SELECT site_id, SUM(target_count) as total '
            . ' FROM send_logs '
            . ' WHERE '
            . '   sent_at BETWEEN ? AND ? '
            . '   AND site_id IN('.$ph.')'
            . ' GROUP BY site_id ';

        $params = array($startTime, $endTime);
        $params = array_merge($params, $siteIds);
        $rows = $this->db->fetchAll($sql, $params);
        return ipull($rows, 'total', 'site_id');
    }

    public function create($siteId, $targetCount)
    {
        $sql = 'INSERT INTO send_logs(site_id,sent_at,target_count) VALUES(?, ?, ?)';
        $this->db->executeUpdate($sql, array($siteId, strftime('%F %T'), $targetCount));
    }

}
