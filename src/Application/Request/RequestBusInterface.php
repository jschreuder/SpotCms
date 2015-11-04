<?php

namespace Spot\Cms\Application\Request;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Spot\Cms\Application\Request\Message\RequestInterface;
use Spot\Cms\Application\Response\Message\ResponseInterface;
use Spot\Cms\Application\Response\ResponseException;

interface RequestBusInterface
{
    public function supports(RequestInterface $request) : bool;

    /**
     * MUST catch all exceptions internally and throw ONLY ResponseException instances
     *
     * @throws  ResponseException
     */
    public function execute(HttpRequest $httpRequest, RequestInterface $requestMessage) : ResponseInterface;
}
