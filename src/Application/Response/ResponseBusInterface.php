<?php

namespace Spot\Cms\Application\Response;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Spot\Cms\Application\Response\Message\ResponseInterface;

interface ResponseBusInterface
{
    /**
     * @param   ResponseInterface $response
     * @return  bool
     */
    public function supports(ResponseInterface $response);

    /**
     * @param   HttpRequest $httpRequest
     * @param   ResponseInterface $response
     * @return  HttpResponse
     */
    public function execute(HttpRequest $httpRequest, ResponseInterface $response);
}
