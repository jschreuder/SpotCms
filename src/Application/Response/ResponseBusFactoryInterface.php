<?php declare(strict_types=1);

namespace Spot\Api\Application\Response;

interface ResponseBusFactoryInterface
{
    public function getResponseBus() : ResponseBusInterface;
}
