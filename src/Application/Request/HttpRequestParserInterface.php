<?php

namespace Spot\Cms\Application\Request;

use Psr\Http\Message\ServerRequestInterface;
use Spot\Cms\Application\Request\Message\RequestInterface;

interface HttpRequestParserInterface
{
    /**
     * MUST catch all exceptions internally and throw only RequestException
     * instances.
     *
     * @param   ServerRequestInterface $httpRequest
     * @return  RequestInterface
     * @throws  RequestException
     */
    public function parse(ServerRequestInterface $httpRequest);
}
