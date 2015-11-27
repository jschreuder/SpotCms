<?php

namespace spec\Spot\Api\Common\Repository;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument\Token\TypeToken;
use Ramsey\Uuid\Uuid;
use Spot\Api\Common\Repository\ObjectRepository;

/** @mixin  \Spot\Api\Common\Repository\ObjectRepository */
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

    public function it_isInitializable()
    {
        $this->shouldHaveType(ObjectRepository::class);
    }

    /**
     * @param  \PDOStatement $pdoStatement
     */
    public function it_shouldBeAbleToCreateANewObject($pdoStatement)
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
    public function it_shouldBeAbleToUpdateAnObject($pdoStatement)
    {
        $uuid = Uuid::uuid4();

        $this->pdo->prepare(new TypeToken('string'))
            ->willReturn($pdoStatement);

        $pdoStatement->execute(['uuid' => $uuid->getBytes(), 'updated' => date('Y-m-d H:i:s')])
            ->shouldBeCalled();

        $this->update($uuid);
    }

    /**
     * @param  \PDOStatement $pdoStatement
     */
    public function it_shouldBeAbleToDeleteAnObject($pdoStatement)
    {
        $uuid = Uuid::uuid4();

        $this->pdo->prepare(new TypeToken('string'))
            ->willReturn($pdoStatement);

        $pdoStatement->execute(['uuid' => $uuid->getBytes()])
            ->shouldBeCalled();

        $this->delete($uuid);
    }
}