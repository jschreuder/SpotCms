<?php declare(strict_types=1);

namespace Spot\Api\Application\Request;

interface HttpRequestParserFactoryInterface
{
    public function getHttpRequestParser() : HttpRequestParserInterface;
}
