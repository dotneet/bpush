<?php
namespace BPush\Model;

use Ramsey\Uuid\Uuid;

class OwnerTokenRepository 
{
    protected $db;

    public function __construct($app)
    {
        $this->db = $app['db'];
    }

    public function findByOwnerId($ownerId)
    {
        $sql = 'SELECT * FROM owner_tokens WHERE owner_id = ?';
        return $this->db->fetchAssoc($sql, array($ownerId));
    }

    public function findByApiToken($apiToken)
    {
        $sql = 'SELECT * FROM owner_tokens WHERE api_token = ?';
        return $this->db->fetchAssoc($sql, array($apiToken));
    }

    public function create($ownerId)
    {
        $apiToken = Uuid::uuid4();
        $sql = 'INSERT INTO owner_tokens(owner_id, api_token,created) '
            .  ' VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE api_token = VALUES(api_token), created = VALUES(created)';
        $this->db->executeUpdate($sql, array($ownerId, $apiToken->toString(), strftime('%F %T')));
        return $this->findByOwnerId($ownerId);
    }

    public function delete($ownerId)
    {
        $sql = 'DELETE FROM owner_tokens WHERE owner_id = ?';
        return $this->executeUpdate($sql, array($ownerId));
    }

}
