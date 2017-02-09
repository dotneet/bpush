<?php
namespace BPush\Model;

class OwnerRepository
{
    use Repository;
    protected $transformClassName = 'Owner';
    protected $db;

    public function __construct($app)
    {
        $this->db = $app['db'];
    }

    public function count()
    {
        $sql = 'SELECT COUNT(*) FROM owners';
        return $this->db->fetchColumn($sql);
    }

    public function find($id)
    {
        $sql = 'SELECT * FROM owners WHERE id = ?';
        $row = $this->db->fetchAssoc($sql, array($id));
        return $this->transform($row);
    }

    public function findByConfirmToken($token)
    {
        $sql = 'SELECT * FROM owners WHERE confirm_token = ? AND status = ?';
        $row = $this->db->fetchAssoc($sql, array($token, Owner::STATUS_UNCONFIRM));
        return $this->transform($row);
    }

    public function findByMail($mail)
    {
        $sql = 'SELECT * FROM owners WHERE mail = ?';
        $row = $this->db->fetchAssoc($sql, array($mail));
        return $this->transform($row);
    }

    public function create($mail, $password)
    {
        $confirmToken = sha1(uniqid());
        $password = Owner::makePasswordHash($password);
        $sql = 'INSERT INTO owners(mail,password,confirm_token,status,grade,created) VALUES(?, ?, ?, ?, ?, ?)';
        $this->db->executeUpdate($sql, array($mail, $password, $confirmToken, Owner::STATUS_UNCONFIRM, Owner::GRADE_FREE, strftime('%F %T')));
        $id = $this->db->lastInsertId();
        return $this->find($id);
    }

    public function updatePassword($id, $password)
    {
        $password = Owner::makePasswordHash($password);
        $sql = 'UPDATE owners SET password = ? WHERE id = ?';
        return $this->db->executeUpdate($sql, array($password, $id));
    }

    public function confirm($id)
    {
        $sql = 'UPDATE owners SET status = ?, confirm_token = NULL WHERE id = ?';
        return $this->db->executeUpdate($sql, array(Owner::STATUS_CONFIRMED, $id));
    }

}
