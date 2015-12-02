<?php declare(strict_types=1);

namespace Spot\Api\ApiCall;

use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParserInterface;
use Spot\Api\Response\Generator\GeneratorInterface;

interface ApiCallInterface extends
    HttpRequestParserInterface,
    ExecutorInterface,
    GeneratorInterface
{
}
