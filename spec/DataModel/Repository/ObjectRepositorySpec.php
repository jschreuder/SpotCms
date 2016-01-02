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

    /**
     * @param  \PDO $pdo
     */
    public function let($pdo)
    {
        $this->pdo = $pdo;
        $this->beConstructedWith($pdo);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ObjectRepository::class);
    }

    /**
     * @param  \PDOStatement $pdoStatement
     */
    public function it_should_be_able_to_create_a_new_object($pdoStatement)
    {
        $uuid = Uuid::uuid4();

        $this->pdo->prepare(new TypeToken('string'))
            ->willReturn($pdoStatement);

        $pdoStatement->execute(['uuid' => $uuid->getBytes(), 'type' => 'test', 'created' => date('Y-m-d H:i:s')])
            ->shouldBeCalled();

        $this->create('test', $uuid);
    }

    /**
     * @param  \PDOStatement $pdoStatement
     */
    public function it_should_be_able_to_update_an_object($pdoStatement)
    {
        $uuid = Uuid::uuid4();

        $this->pdo->prepare(new TypeToken('string'))
            ->willReturn($pdoStatement);

        $pdoStatement->execute(['uuid' => $uuid->getBytes(), 'type' => 'test', 'updated' => date('Y-m-d H:i:s')])
            ->shouldBeCalled();

        $this->update('test', $uuid);
    }

    /**
     * @param  \PDOStatement $pdoStatement
     */
    public function it_should_be_able_to_delete_an_object($pdoStatement)
    {
        $uuid = Uuid::uuid4();

        $this->pdo->prepare(new TypeToken('string'))
            ->willReturn($pdoStatement);

        $pdoStatement->execute(['uuid' => $uuid->getBytes(), 'type' => 'test'])
            ->shouldBeCalled();

        $this->delete('test', $uuid);
    }
}
