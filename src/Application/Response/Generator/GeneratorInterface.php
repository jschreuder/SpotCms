<?php declare(strict_types=1);

namespace Spot\Cms\Application\Response\Generator;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Spot\Cms\Application\Response\Message\ResponseInterface;

interface GeneratorInterface
{
    /**
     * Takes a Response message (and HTTP request for reference) and generates
     * a HTTP response.
     *
     * MUST catch all exceptions internally and never throw any Exception.
     */
    public function generateResponse(ResponseInterface $request, HttpRequest $httpRequest) : HttpResponse;
}
