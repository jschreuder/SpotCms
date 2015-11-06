<?php declare(strict_types=1);

namespace Spot\Api\Application\Request\Executor;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Spot\Api\Application\Request\Message\RequestInterface;
use Spot\Api\Application\Response\Message\ResponseInterface;

interface ExecutorInterface
{
    /**
     * Takes a Request message (and HTTP request for reference) and executes it
     * to get the result in a Response message.
     *
     * MUST catch all exceptions internally and throw only ResponseException
     * instances.
     */
    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface;
}
