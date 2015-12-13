<?php declare(strict_types=1);

namespace Spot\Common\ApiBuilder;

use Spot\Api\Request\Executor\ExecutorInterface;

interface ExecutorFactoryInterface
{
    public function getExecutor() : ExecutorInterface;
}
