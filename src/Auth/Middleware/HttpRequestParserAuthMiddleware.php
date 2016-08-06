<?php declare(strict_types = 1);

namespace Spot\Auth\Middleware;

use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\UnauthorizedRequest;
use Spot\Api\Request\RequestInterface;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\Handler\LoginHandler;
use Spot\Auth\AuthenticationService;
use Spot\Auth\TokenService;

class HttpRequestParserAuthMiddleware implements HttpRequestParserInterface
{
    use LoggableTrait;

    /** @var  HttpRequestParserInterface */
    private $httpRequestParser;

    /** @var  TokenService */
    private $tokenService;

    /** @var  AuthenticationService */
    private $authenticationService;

    /** @var  string[] */
    private $publicMessageNames = [
        LoginHandler::MESSAGE,
    ];

    /** @var  LoggerInterface */
    private $logger;

    public function __construct(
        HttpRequestParserInterface $httpRequestParser,
        TokenService $tokenService,
        AuthenticationService $authenticationService,
        array $publicMessageNames,
        LoggerInterface $logger
    )
    {
        $this->httpRequestParser = $httpRequestParser;
        $this->tokenService = $tokenService;
        $this->authenticationService = $authenticationService;
        $this->publicMessageNames = array_merge($this->publicMessageNames, $publicMessageNames);
        $this->logger = $logger;
    }

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $request = $this->httpRequestParser->parseHttpRequest($httpRequest, $attributes);
        if (!$this->isAllowed($request, $httpRequest)) {
            return new UnauthorizedRequest([], $httpRequest);
        }
        return $request;
    }

    private function isAllowed(RequestInterface $request, ServerHttpRequest $httpRequest)
    {
        if (in_array($request->getRequestName(), $this->publicMessageNames, true)) {
            return true;
        }

        try {
            $this->tokenService->getToken(
                Uuid::fromString($httpRequest->getHeaderLine('Authentication-Token')),
                $httpRequest->getHeaderLine('Authentication-Pass-Code')
            );
            return true;
        } catch (\Throwable $exception) {
            if (!$exception instanceof AuthException) {
                $this->log(LogLevel::ERROR, $exception->getMessage());
            }
            return false;
        }
    }
}
