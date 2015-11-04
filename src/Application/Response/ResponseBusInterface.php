<?php

namespace Spot\Cms\Application\Response;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;

interface ResponseBusInterface
{
    /**
     * @param   HttpRequest $httpRequest
     * @param   ResponseInterface $response
     * @return  HttpResponse
     */
    public function execute(HttpRequest $httpRequest, ResponseInterface $response);
}
