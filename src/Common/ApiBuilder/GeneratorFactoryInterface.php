<?php declare(strict_types=1);

namespace Spot\Common\ApiBuilder;

use Spot\Api\Response\Generator\GeneratorInterface;

interface GeneratorFactoryInterface
{
    public function getGenerator() : GeneratorInterface;
}
