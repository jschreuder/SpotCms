<?php declare(strict_types = 1);

namespace Spot\Auth\Middleware;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Ramsey\Uuid\Uuid;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\AuthenticationService;
use Spot\Auth\TokenService;

class AuthMiddleware implements ServerMiddlewareInterface
{
    /** @var  TokenService */
    private $tokenService;

    /** @var  AuthenticationService */
    private $authenticationService;

    /** @var  string[] */
    private $publicUris = [
        '/api/auth/login',
    ];

    public function __construct(
        TokenService $tokenService,
        AuthenticationService $authenticationService,
        array $publicUris
    )
    {
        $this->tokenService = $tokenService;
        $this->authenticationService = $authenticationService;
        $this->publicUris = array_merge($this->publicUris, $publicUris);
    }

    public function process(ServerHttpRequest $request, DelegateInterface $delegate) : ResponseInterface
    {
        if (!$this->isAllowed($request)) {
            return new JsonApiErrorResponse(['UNAUTHORIZED' => 'Not authorized'], 401);
        }
        return $delegate->next($request);
    }

    private function isAllowed(ServerHttpRequest $request)
    {
        if (in_array($request->getUri()->getPath(), $this->publicUris, true)) {
            return true;
        }

        if (!Uuid::isValid($request->getHeaderLine('Authentication-Token'))) {
            return false;
        }

        try {
            $this->tokenService->getToken(
                Uuid::fromString($request->getHeaderLine('Authentication-Token')),
                $request->getHeaderLine('Authentication-Pass-Code')
            );
            return true;
        } catch (AuthException $exception) {
            return false;
        }
    }
}
