<?php declare(strict_types = 1);

namespace Spot\DataModel\Repository;

trait SqlRepositoryTrait
{
    /** @var  \PDO */
    private $pdo;

    protected function executeSql(string $sql, array $parameters = []) : \PDOStatement
    {
        $query = $this->pdo->prepare($sql);
        $query->execute($parameters);
        return $query;
    }

    protected function getRow(string $sql, array $parameters) : array
    {
        $query = $this->executeSql($sql, $parameters);
        if ($query->rowCount() !== 1) {
            throw new NoUniqueResultException('Expected a unique result, but got ' . $query->rowCount() . ' results.');
        }
        return (array) $query->fetch(\PDO::FETCH_ASSOC);
    }
}
