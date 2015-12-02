<?php declare(strict_types=1);

namespace Spot\Api\Request;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\ResponseException;

interface RequestBusInterface
{
    /**
     * MUST catch all exceptions internally and throw ONLY ResponseException instances
     *
     * @throws  ResponseException
     */
    public function execute(HttpRequest $httpRequest, RequestInterface $requestMessage) : ResponseInterface;
}
