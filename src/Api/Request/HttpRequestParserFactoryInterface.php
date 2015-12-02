<?php declare(strict_types=1);

namespace Spot\Api\Request;

interface HttpRequestParserFactoryInterface
{
    public function getHttpRequestParser() : HttpRequestParserInterface;
}
