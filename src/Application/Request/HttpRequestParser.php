<?php declare(strict_types=1);

namespace Spot\Cms\Application\Request;

use FastRoute\Dispatcher as Router;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Cms\Application\Request\Message\NotFound;
use Spot\Cms\Application\Request\Message\RequestInterface;
use Spot\Cms\Application\Request\Message\ServerError;

class HttpRequestParser implements HttpRequestParserInterface
{
    /** @var  Router */
    private $router;

    /** @var  LoggerInterface */
    private $logger;

    public function __construct(Router $router, LoggerInterface $logger)
    {
        $this->router = $router;
        $this->logger = $logger;
    }

    /** {@inheritdoc} */
    public function parseHttpRequest(ServerRequestInterface $httpRequest) : RequestInterface
    {
        $method = $httpRequest->getMethod();
        $path = $httpRequest->getUri()->getPath();
        $routeInfo = $this->router->dispatch($method, $path);
        try {
            switch ($routeInfo[0]) {
                case Router::NOT_FOUND:
                case Router::METHOD_NOT_ALLOWED:
                    $this->log(LogLevel::INFO, 'No route found for ' . $method . ' ' . $path);
                    $request = new NotFound();
                    break;
                case Router::FOUND:
                    $parser = $routeInfo[1];
                    $uriParameters = $routeInfo[2];
                    $request = $parser($httpRequest, $uriParameters);
                    $this->log(LogLevel::INFO, 'Found route found for ' . $method . ' ' . $path);
                    break;
                default:
                    throw new \RuntimeException('Routing errored for ' . $method . ' ' . $path);
            }
        } catch (\Exception $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            $request = new ServerError();
        }

        if (!$request instanceof RequestInterface) {
            $this->log(LogLevel::ERROR, 'HttpRequestParser did not result in a Request message.');
            return new ServerError();
        }
        return $request;
    }

    protected function log(string $message, string $level)
    {
        $this->logger->log($level, '[HttpRequestParser] ' . $message);
    }
}
