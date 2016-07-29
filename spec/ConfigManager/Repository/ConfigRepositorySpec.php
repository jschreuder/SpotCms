<?php

namespace spec\Spot\ConfigManager\Repository;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Spot\ConfigManager\ConfigType\ConfigTypeContainerInterface;
use Spot\ConfigManager\ConfigType\ConfigTypeInterface;
use Spot\ConfigManager\Entity\ConfigCollection;
use Spot\ConfigManager\Repository\ConfigRepository;
use Spot\DataModel\Repository\ObjectRepository;

/** @mixin  ConfigRepository */
class ConfigRepositorySpec extends ObjectBehavior
{
    /** @var  \PDO */
    private $pdo;

    /** @var  ConfigTypeContainerInterface */
    private $typeContainer;

    /** @var  ObjectRepository */
    private $objectRepository;

    public function let(\PDO $pdo, ConfigTypeContainerInterface $container, ObjectRepository $objectRepository)
    {
        $this->pdo = $pdo;
        $this->typeContainer = $container;
        $this->objectRepository = $objectRepository;
        $this->beConstructedWith($pdo, $container, $objectRepository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ConfigRepository::class);
    }

    public function it_can_create_a_collection(
        ConfigCollection $collection,
        ConfigTypeInterface $type,
        \PDOStatement $statement,
        \PDOStatement $itemStatement1,
        \PDOStatement $itemStatement2
    )
    {
        $uuid = Uuid::uuid4();
        $typeName = 'exampleType';
        $name = 'example';
        $items = ['k1' => 'v1', 'k2' => 'v2'];

        $collection->getUuid()->willReturn($uuid);
        $collection->getType()->willReturn($type);
        $collection->getName()->willReturn($name);
        $collection->getItems()->willReturn($items);
        $collection->metaDataSetInsertTimestamp(new Argument\Token\TypeToken(\DateTimeInterface::class))
            ->shouldBeCalled();
        $type->getTypeName()->willReturn($typeName);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->objectRepository->create(ConfigCollection::TYPE, $uuid)
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('INSERT INTO config_collections'))
            ->willReturn($statement);
        $statement->execute([
            'config_collection_uuid' => $uuid->getBytes(),
            'type' => $typeName,
            'name' => $name,
        ])->shouldBeCalled();


        $this->pdo->prepare(new Argument\Token\StringContainsToken('INSERT INTO config_items'))
            ->willReturn($itemStatement1, $itemStatement2, false);

        $itemStatement1->execute([
            'config_collection_uuid' => $uuid->getBytes(),
            'name' => 'k1',
            'value' => json_encode('v1'),
        ])->shouldBeCalled();
        $itemStatement2->execute([
            'config_collection_uuid' => $uuid->getBytes(),
            'name' => 'k2',
            'value' => json_encode('v2'),
        ])->shouldBeCalled();

        $this->pdo->commit()
            ->shouldBeCalled();

        $this->create($collection);
    }

    public function it_will_roll_back_on_error_during_create(ConfigCollection $collection)
    {
        $uuid = Uuid::uuid4();
        $collection->getUuid()->willReturn($uuid);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->objectRepository->create(ConfigCollection::TYPE, $uuid)
            ->willThrow(new \RuntimeException());

        $this->pdo->rollBack()
            ->shouldBeCalled();

        $this->shouldThrow(\RuntimeException::class)->duringCreate($collection);
    }

    public function it_can_update_a_collection(
        ConfigCollection $collection,
        \PDOStatement $itemStatement1,
        \PDOStatement $itemStatement2
    )
    {
        $uuid = Uuid::uuid4();
        $items = ['k1' => 'v1', 'k2' => 'v2'];

        $collection->getUuid()->willReturn($uuid);
        $collection->getItems()->willReturn($items);
        $collection->metaDataSetUpdateTimestamp(new Argument\Token\TypeToken(\DateTimeImmutable::class))
            ->willReturn($collection);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->objectRepository->update(ConfigCollection::TYPE, $uuid)
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('UPDATE config_items'))
            ->willReturn($itemStatement1, $itemStatement2, false);

        $itemStatement1->execute([
            'config_collection_uuid' => $uuid->getBytes(),
            'name' => 'k1',
            'value' => json_encode('v1'),
        ])->shouldBeCalled();
        $itemStatement2->execute([
            'config_collection_uuid' => $uuid->getBytes(),
            'name' => 'k2',
            'value' => json_encode('v2'),
        ])->shouldBeCalled();

        $this->pdo->commit()
            ->shouldBeCalled();

        $this->update($collection);
    }

    public function it_will_rollBack_on_error_during_update(
        ConfigCollection $collection,
        \PDOStatement $itemStatement1
    )
    {
        $uuid = Uuid::uuid4();
        $items = ['k1' => 'v1', 'k2' => 'v2'];

        $collection->getUuid()->willReturn($uuid);
        $collection->getItems()->willReturn($items);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('UPDATE config_items'))
            ->willReturn($itemStatement1, false);

        $itemStatement1->execute([
            'config_collection_uuid' => $uuid->getBytes(),
            'name' => 'k1',
            'value' => json_encode('v1'),
        ])->willThrow(new \RuntimeException());

        $this->pdo->rollBack()
            ->shouldBeCalled();

        $this->shouldThrow(\RuntimeException::class)->duringUpdate($collection);
    }

    public function it_can_delete_a_collection(ConfigCollection $collection)
    {
        $uuid = Uuid::uuid4();
        $collection->getUuid()->willReturn($uuid);

        $this->objectRepository->delete(ConfigCollection::TYPE, $uuid)
            ->shouldBeCalled();

        $this->delete($collection);
    }

    public function it_can_get_collections_by_type(
        ConfigTypeInterface $type,
        \PDOStatement $statement,
        \PDOStatement $itemStatement
    )
    {
        $collection1Uuid = Uuid::uuid4();
        $collection1Items = ['k1' => 'v1', 'k2' => 'v2'];
        $collection2Uuid = Uuid::uuid4();
        $collection2Items = ['k1' => 'v3', 'k2' => 'v4'];

        $typeName = 'specType';
        $type->getTypeName()->willReturn($typeName);
        $type->getDefaultItems()->willReturn(['k1' => null, 'k2' => null]);

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM config_collections'))
            ->willReturn($statement);
        $statement->execute(['type' => $typeName])->shouldBeCalled();
        $statement->fetch(\PDO::FETCH_ASSOC)->willReturn(
            [
                'config_collection_uuid' => $collection1Uuid->getBytes(),
                'type' => $typeName,
                'name' => $typeName . '1',
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ],
            [
                'config_collection_uuid' => $collection2Uuid->getBytes(),
                'type' => $typeName,
                'name' => $typeName . '2',
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ],
            false
        );

        $this->pdo->quote($collection1Uuid->getBytes())->willReturn('"uuid1"');
        $this->pdo->quote($collection2Uuid->getBytes())->willReturn('"uuid2"');
        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM config_items'))
            ->willReturn($itemStatement);
        $itemStatement->execute([])->shouldBeCalled();
        $itemStatement->fetch(\PDO::FETCH_ASSOC)->willReturn(
            [
                'config_collection_uuid' => $collection1Uuid->getBytes(),
                'name' => 'k1',
                'value' => '"v1"',
            ],
            [
                'config_collection_uuid' => $collection2Uuid->getBytes(),
                'name' => 'k1',
                'value' => '"v3"',
            ],
            [
                'config_collection_uuid' => $collection2Uuid->getBytes(),
                'name' => 'k2',
                'value' => '"v4"',
            ],
            [
                'config_collection_uuid' => $collection1Uuid->getBytes(),
                'name' => 'k2',
                'value' => '"v2"',
            ],
            false
        );

        $this->typeContainer->getType($typeName)->willReturn($type);

        $collections = $this->getCollectionsByType($type);
        $collections[0]->getUuid()->shouldBeLike($collection1Uuid);
        $collections[0]->getItems()->shouldReturn($collection1Items);
        $collections[1]->getUuid()->shouldBeLike($collection2Uuid);
        $collections[1]->getItems()->shouldReturn($collection2Items);
    }

    public function it_can_get_a_collection_by_type_and_name(
        ConfigTypeInterface $type,
        \PDOStatement $statement,
        \PDOStatement $itemStatement
    )
    {
        $collectionName = 'colName';
        $collectionUuid = Uuid::uuid4();
        $collectionItems = ['k1' => 'v1', 'k2' => 'v2'];

        $typeName = 'specType';
        $type->getTypeName()->willReturn($typeName);
        $type->getDefaultItems()->willReturn(['k1' => null, 'k2' => null]);

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM config_collections'))
            ->willReturn($statement);
        $statement->execute(['type' => $typeName, 'name' => $collectionName])->shouldBeCalled();
        $statement->fetch(\PDO::FETCH_ASSOC)->willReturn(
            [
                'config_collection_uuid' => $collectionUuid->getBytes(),
                'type' => $typeName,
                'name' => $collectionName,
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ]
        );

        $this->pdo->quote($collectionUuid->getBytes())->willReturn('"uuid1"');
        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM config_items'))
            ->willReturn($itemStatement);
        $itemStatement->execute([])->shouldBeCalled();
        $itemStatement->fetch(\PDO::FETCH_ASSOC)->willReturn(
            [
                'config_collection_uuid' => $collectionUuid->getBytes(),
                'name' => 'k1',
                'value' => '"v1"',
            ],
            [
                'config_collection_uuid' => $collectionUuid->getBytes(),
                'name' => 'k2',
                'value' => '"v2"',
            ],
            false
        );

        $this->typeContainer->getType($typeName)->willReturn($type);

        $collection = $this->getCollectionByTypeAndName($type, $collectionName);
        $collection->getUuid()->shouldBeLike($collectionUuid);
        $collection->getItems()->shouldReturn($collectionItems);
    }
}
