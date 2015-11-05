<?php

namespace Spot\Cms\Application\Repository;

use Ramsey\Uuid\UuidInterface;

class ObjectRepository
{
    /** @var  \PDO */
    private $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function create(string $type, UuidInterface $uuid)
    {
        $this->db->prepare('
            INSERT INTO objects (uuid, type, created, updated) VALUES (:uuid, :type, :created, :created)
        ')->execute(['uuid' => $uuid->getBytes(), 'type' => $type, 'created' => date('Y-m-d H:i:s')]);
    }

    public function update(UuidInterface $uuid)
    {
        $this->db->prepare('UPDATE objects SET updated = :updated WHERE uuid = :uuid')
            ->execute(['uuid' => $uuid->getBytes(), 'updated' => date('Y-m-d H:i:s')]);
    }

    public function delete(UuidInterface $uuid)
    {
        $this->db->prepare('DELETE FROM objects WHERE uuid = :uuid')
            ->execute(['uuid' => $uuid->getBytes()]);
    }
}
