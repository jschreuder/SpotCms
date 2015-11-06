<?php declare(strict_types=1);

namespace Spot\Api\Application\Response;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Spot\Api\Application\Response\Message\ResponseInterface;

interface ResponseBusInterface
{
    public function supports(ResponseInterface $response) : bool;

    /**
     * MUST result in a HttpResponse, it may never result in an Exception or error.
     */
    public function execute(HttpRequest $httpRequest, ResponseInterface $response) : HttpResponse;
}
