<?php

namespace Spot\Cms\Application\Request;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Spot\Cms\Application\Request\Message\RequestInterface;
use Spot\Cms\Application\Response\Message\ResponseInterface;
use Spot\Cms\Application\Response\ResponseException;

interface RequestBusInterface
{
    /**
     * @param   RequestInterface $request
     * @return  bool
     */
    public function supports(RequestInterface $request);

    /**
     * MUST catch all exceptions internally and throw ONLY ResponseException instances
     *
     * @param   HttpRequest $httpRequest
     * @param   RequestInterface $requestMessage
     * @return  ResponseInterface
     * @throws  ResponseException
     */
    public function execute(HttpRequest $httpRequest, RequestInterface $requestMessage);
}
