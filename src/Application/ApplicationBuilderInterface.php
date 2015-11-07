<?php declare(strict_types=1);

namespace Spot\Api\Application;

use Spot\Api\Application\Request\HttpRequestParserInterface;
use Spot\Api\Application\Request\RequestBusInterface;
use Spot\Api\Application\Response\ResponseBusInterface;

interface ApplicationBuilderInterface
{
    public function addParser(string $method, string $path, $httpRequestParser) : self;

    public function addRequestExecutor(string $requestName, $executor) : self;

    public function addResponseGenerator(string $responseName, $generator) : self;

    public function addApiCall(string $method, string $path, string $name, $apiCall) : self;

    public function getHttpRequestParser() : HttpRequestParserInterface;

    public function getRequestBus() : RequestBusInterface;

    public function getResponseBus() : ResponseBusInterface;
}
