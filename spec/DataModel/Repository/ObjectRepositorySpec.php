<?php

namespace spec\Spot\DataModel\Repository;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument\Token\TypeToken;
use Ramsey\Uuid\Uuid;
use Spot\DataModel\Repository\ObjectRepository;

/** @mixin  \Spot\DataModel\Repository\ObjectRepository */
class ObjectRepositorySpec extends ObjectBehavior
{
    /** @var  \PDO */
    private $pdo;

    public function let(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->beConstructedWith($pdo);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ObjectRepository::class);
    }

    public function it_should_be_able_to_create_a_new_object(\PDOStatement $pdoStatement)
    {
        $uuid = Uuid::uuid4();

        $this->pdo->prepare(new TypeToken('string'))
            ->willReturn($pdoStatement);

        $pdoStatement->execute(['uuid' => $uuid->getBytes(), 'type' => 'test', 'created' => date('Y-m-d H:i:s')])
            ->shouldBeCalled();

        $this->create('test', $uuid);
    }

    public function it_should_be_able_to_update_an_object(\PDOStatement $pdoStatement)
    {
        $uuid = Uuid::uuid4();

        $this->pdo->prepare(new TypeToken('string'))
            ->willReturn($pdoStatement);

        $pdoStatement->execute(['uuid' => $uuid->getBytes(), 'type' => 'test', 'updated' => date('Y-m-d H:i:s')])
            ->shouldBeCalled();

        $this->update('test', $uuid);
    }

    public function it_should_be_able_to_delete_an_object(\PDOStatement $pdoStatement)
    {
        $uuid = Uuid::uuid4();

        $this->pdo->prepare(new TypeToken('string'))
            ->willReturn($pdoStatement);

        $pdoStatement->execute(['uuid' => $uuid->getBytes(), 'type' => 'test'])
            ->shouldBeCalled();

        $this->delete('test', $uuid);
    }
}
