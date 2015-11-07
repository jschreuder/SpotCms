<?php declare(strict_types=1);

namespace Spot\Api\Application;

use Spot\Api\Application\Request\Executor\ExecutorInterface;
use Spot\Api\Application\Request\HttpRequestParserInterface;
use Spot\Api\Application\Response\Generator\GeneratorInterface;

interface ApiCallInterface extends
    HttpRequestParserInterface,
    ExecutorInterface,
    GeneratorInterface
{
}
