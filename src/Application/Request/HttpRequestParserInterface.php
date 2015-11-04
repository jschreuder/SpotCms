<?php

namespace Spot\Cms\Application\Request;

use Psr\Http\Message\ServerRequestInterface;
use Spot\Cms\Application\Request\Message\RequestInterface;

interface HttpRequestParserInterface
{
    /**
     * @param   ServerRequestInterface $httpRequest
     * @return  RequestInterface
     */
    public function parse(ServerRequestInterface $httpRequest);
}
