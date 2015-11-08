<?php declare(strict_types=1);

namespace Spot\Api\Application;

use Spot\Api\Application\Request\HttpRequestParserInterface;

interface HttpRequestParserFactoryInterface
{
    public function getHttpRequestParser() : HttpRequestParserInterface;
}
