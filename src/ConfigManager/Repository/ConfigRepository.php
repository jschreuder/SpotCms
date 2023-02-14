<?php declare(strict_types = 1);

namespace Spot\ConfigManager\Repository;

use PDO;
use Ramsey\Uuid\Uuid;
use Spot\ConfigManager\ConfigType\ConfigTypeContainerInterface;
use Spot\ConfigManager\ConfigType\ConfigTypeInterface;
use Spot\ConfigManager\Entity\ConfigCollection;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\DataModel\Repository\SqlRepositoryTrait;

class ConfigRepository
{
    use SqlRepositoryTrait;

    public function __construct(
        private PDO $pdo,
        private ConfigTypeContainerInterface $typeContainer,
        private ObjectRepository $objectRepository
    )
    {
        $this->pdo = $pdo;
    }

    public function create(ConfigCollection $collection): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->objectRepository->create(ConfigCollection::TYPE, $collection->getUuid());
            $this->executeSql('
                INSERT INTO config_collections (config_collection_uuid, type, name)
                     VALUES (:config_collection_uuid, :type, :name)
            ', [
                'config_collection_uuid' => $collection->getUuid()->getBytes(),
                'type' => $collection->getType()->getTypeName(),
                'name' => $collection->getName(),
            ]);
            $this->createItems($collection);
            $this->pdo->commit();
            $collection->metaDataSetInsertTimestamp(new \DateTimeImmutable());
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function createItems(ConfigCollection $collection): void
    {
        foreach ($collection->getItems() as $item => $value) {
            $this->executeSql('
                INSERT INTO config_items (config_collection_uuid, name, value)
                     VALUES (:config_collection_uuid, :name, :value)
            ', [
                'config_collection_uuid' => $collection->getUuid()->getBytes(),
                'name' => $item,
                'value' => json_encode($value),
            ]);
        }
    }

    public function update(ConfigCollection $collection)
    {
        $this->pdo->beginTransaction();
        try {
            $this->updateItems($collection);
            $this->objectRepository->update(ConfigCollection::TYPE, $collection->getUuid());
            $collection->metaDataSetUpdateTimestamp(new \DateTimeImmutable());
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function updateItems(ConfigCollection $collection): void
    {
        foreach ($collection->getItems() as $item => $value) {
            $this->executeSql('
                UPDATE config_items
                   SET value = :value
                 WHERE config_collection_uuid = :config_collection_uuid AND name = :name
            ', [
                'config_collection_uuid' => $collection->getUuid()->getBytes(),
                'name' => $item,
                'value' => json_encode($value),
            ]);
        }
    }

    public function delete(ConfigCollection $collection): void
    {
        // The database constraint should cascade the delete to the collection and its items
        $this->objectRepository->delete(ConfigCollection::TYPE, $collection->getUuid());
    }

    private function getCollectionFromRow(array $row): ConfigCollection
    {
        return (new ConfigCollection(
            Uuid::fromBytes($row['config_collection_uuid']),
            $this->typeContainer->getType($row['type']),
            $row['name']
        ))
            ->metaDataSetInsertTimestamp(new \DateTimeImmutable($row['created']))
            ->metaDataSetUpdateTimestamp(new \DateTimeImmutable($row['updated']));
    }

    public function getCollectionsByType(ConfigTypeInterface $type): array
    {
        $query = $this->executeSql('
                SELECT config_collection_uuid, type, name, created, updated
                  FROM config_collections
            INNER JOIN objects ON (page_uuid = uuid AND type = "configCollections")
                 WHERE type = :type
        ', [
            'type' => $type->getTypeName(),
        ]);

        $collections = [];
        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $collections[] = $this->getCollectionFromRow($row);
        }
        $this->getItemsForCollections($collections);
        return $collections;
    }

    public function getCollectionByTypeAndName(ConfigTypeInterface $type, string $name): ConfigCollection
    {
        $query = $this->executeSql('
                SELECT config_collection_uuid, type, name, created, updated
                  FROM config_collections
            INNER JOIN objects ON (page_uuid = uuid AND type = "configCollections")
                 WHERE type = :type
                   AND name = :name
        ', [
            'type' => $type->getTypeName(),
            'name' => $name,
        ]);

        $collection = $this->getCollectionFromRow($query->fetch(\PDO::FETCH_ASSOC));
        $this->getItemsForCollections([$collection]);
        return $collection;
    }

    /**
     * @param   ConfigCollection[] $collections
     */
    private function getItemsForCollections(array $collections): void
    {
        $uuids = [];
        /** @var  ConfigCollection[] $collectionsByUuid */
        $collectionsByUuid = [];
        foreach ($collections as $collection) {
            $uuids[] = $this->pdo->quote($collection->getUuid()->getBytes());
            $collectionsByUuid[$collection->getUuid()->getBytes()] = $collection;
        }

        $query = $this->executeSql('
                SELECT config_collection_uuid, name, value
                  FROM config_items
                 WHERE config_collection_uuid IN ("' . implode('", "', $uuids) . '")
        ', []);

        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $collection = $collectionsByUuid[$row['config_collection_uuid']];
            $collection->setItem($row['name'], json_decode($row['value']));
        }
    }
}
