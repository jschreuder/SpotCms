<?php declare(strict_types=1);

namespace Spot\Api\Request;

interface RequestBusFactoryInterface
{
    public function getRequestBus() : RequestBusInterface;
}