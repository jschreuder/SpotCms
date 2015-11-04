<?php

namespace Spot\Cms\Application\Request;

use Psr\Http\Message\ServerRequestInterface;

interface HttpRequestParserInterface
{
    /**
     * @param   ServerRequestInterface $httpRequest
     * @return  RequestInterface
     */
    public function parse(ServerRequestInterface $httpRequest);
}
