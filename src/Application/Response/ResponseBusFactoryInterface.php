<?php declare(strict_types=1);

namespace Spot\Api\Application;

use Spot\Api\Application\Response\ResponseBusInterface;

interface ResponseBusFactoryInterface
{
    public function getResponseBus() : ResponseBusInterface;
}
