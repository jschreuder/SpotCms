<?php declare(strict_types=1);

namespace Spot\Cms\Application;

use Spot\Cms\Application\Request\Executor\ExecutorInterface;
use Spot\Cms\Application\Request\HttpRequestParserInterface;
use Spot\Cms\Application\Request\Message\RequestInterface;
use Spot\Cms\Application\Response\Generator\GeneratorInterface;
use Spot\Cms\Application\Response\Message\ResponseInterface;

interface ApiCallInterface extends
    HttpRequestParserInterface, RequestInterface, ExecutorInterface, ResponseInterface, GeneratorInterface
{
}
