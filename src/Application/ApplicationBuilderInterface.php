<?php declare(strict_types=1);

namespace Spot\Cms\Application;

use Spot\Cms\Application\Request\Executor\ExecutorInterface;
use Spot\Cms\Application\Request\HttpRequestParserInterface;
use Spot\Cms\Application\Request\RequestBusInterface;
use Spot\Cms\Application\Response\Generator\GeneratorInterface;
use Spot\Cms\Application\Response\ResponseBusInterface;

interface ApplicationBuilderInterface
{
    public function addParser(string $method, string $path, HttpRequestParserInterface $httpRequestParser) : self;

    public function addRequestExecutor(string $requestName, ExecutorInterface $executor) : self;

    public function addResponseGenerator(string $responseName, GeneratorInterface $generator) : self;

    public function addApiCall(string $method, string $path, string $name, ApiCallInterface $apiCall) : self;

    public function getHttpRequestParser() : HttpRequestParserInterface;

    public function getRequestBus() : RequestBusInterface;

    public function getResponseBus() : ResponseBusInterface;
}
