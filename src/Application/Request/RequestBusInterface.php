<?php declare(strict_types=1);

namespace Spot\Api\Application\Request;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Spot\Api\Application\Request\Message\RequestInterface;
use Spot\Api\Application\Response\Message\ResponseInterface;
use Spot\Api\Application\Response\ResponseException;

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
