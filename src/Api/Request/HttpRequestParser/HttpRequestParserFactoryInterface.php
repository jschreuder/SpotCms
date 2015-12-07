<?php declare(strict_types=1);

namespace Spot\Api\Request\HttpRequestParser;

use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;

interface HttpRequestParserFactoryInterface
{
    public function getHttpRequestParser() : HttpRequestParserInterface;
}
