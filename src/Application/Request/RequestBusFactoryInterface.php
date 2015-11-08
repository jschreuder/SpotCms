<?php declare(strict_types=1);

namespace Spot\Api\Application;

use Spot\Api\Application\Request\RequestBusInterface;

interface RequestBusFactoryInterface
{
    public function getRequestBus() : RequestBusInterface;
}
