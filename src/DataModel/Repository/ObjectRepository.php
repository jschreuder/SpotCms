<?php declare(strict_types = 1);

namespace Spot\DataModel\Repository;

use Ramsey\Uuid\UuidInterface;

class ObjectRepository
{
    /** @var  \PDO */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(string $type, UuidInterface $uuid)
    {
        $this->pdo->prepare('
            INSERT INTO objects (uuid, type, created, updated) VALUES (:uuid, :type, :created, :created)
        ')->execute(['uuid' => $uuid->getBytes(), 'type' => $type, 'created' => date('Y-m-d H:i:s')]);
    }

    public function update(string $type, UuidInterface $uuid)
    {
        $this->pdo->prepare('UPDATE objects SET updated = :updated WHERE uuid = :uuid AND type = :type')
            ->execute(['uuid' => $uuid->getBytes(), 'type' => $type, 'updated' => date('Y-m-d H:i:s')]);
    }

    public function delete(string $type, UuidInterface $uuid)
    {
        $this->pdo->prepare('DELETE FROM objects WHERE uuid = :uuid AND type = :type')
            ->execute(['uuid' => $uuid->getBytes(), 'type' => $type]);
    }
}
