<?php

namespace spec\Spot\ConfigManager\Repository;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\ConfigManager\ConfigType\ConfigTypeContainerInterface;
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
}
