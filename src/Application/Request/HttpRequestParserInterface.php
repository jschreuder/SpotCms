<?php declare(strict_types=1);

namespace Spot\Cms\Application\Request;

use Psr\Http\Message\ServerRequestInterface as HttpRequest;
use Spot\Cms\Application\Request\Message\RequestInterface;

interface HttpRequestParserInterface
{
    /**
     * MUST throw a RequestException on failure to validate the data, may not
     * throw any other type of Exception
     *
     * @throws  RequestException
     */
    public function validateHttpRequest(HttpRequest $httpRequest);

    /**
     * MUST catch all exceptions internally and throw only RequestException
     * instances.
     *
     * @throws  RequestException
     */
    public function parseHttpRequest(HttpRequest $httpRequest) : RequestInterface;
}
