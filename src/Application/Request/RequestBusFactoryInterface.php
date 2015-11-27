<?php declare(strict_types=1);

namespace Spot\Api\Application\Request;

interface RequestBusFactoryInterface
{
    public function getRequestBus() : RequestBusInterface;
}
