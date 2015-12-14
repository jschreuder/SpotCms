<?php declare(strict_types = 1);

namespace Spot\Api\Request\Handler;

use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;

interface RequestHandlerInterface extends HttpRequestParserInterface, ExecutorInterface
{
}
