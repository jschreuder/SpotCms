<?php declare(strict_types=1);

namespace Spot\Api\Response;

interface ResponseBusFactoryInterface
{
    public function getResponseBus() : ResponseBusInterface;
}
