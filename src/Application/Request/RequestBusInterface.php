<?php

namespace Spot\Cms\Application\Request;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Spot\Cms\Application\Request\Message\RequestInterface;
use Spot\Cms\Application\Response\ResponseInterface;

interface RequestBusInterface
{
    /**
     * @param   RequestInterface $request
     * @return  bool
     */
    public function supports(RequestInterface $request);

    /**
     * @param   HttpRequest $httpRequest
     * @param   RequestInterface $requestMessage
     * @return  ResponseInterface
     */
    public function execute(HttpRequest $httpRequest, RequestInterface $requestMessage);
}
