<?php

namespace spec\Spot\Auth\Middleware;

use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Auth\Entity\Token;
use Spot\Auth\Middleware\AuthMiddleware;
use Spot\Auth\TokenService;

/** @mixin  AuthMiddleware */
class AuthMiddlewareSpec extends ObjectBehavior
{
    /** @var  TokenService */
    private $tokenService;

    private string $publicMessageName = '/test/public';

    public function let(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
        $this->beConstructedWith($tokenService, [$this->publicMessageName]);
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(AuthMiddleware::class);
    }

    public function it_can_process_a_http_request(
        ServerRequestInterface $request,
        UriInterface $uri,
        RequestHandlerInterface $handler,
        Token $actualToken,
        ResponseInterface $response
    )
    {
        $request->getUri()->willReturn($uri);
        $uri->getPath()->willReturn($this->publicMessageName);
        $token = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));

        $request->getHeaderLine('Authentication-Token')->willReturn($token);
        $request->getHeaderLine('Authentication-Pass-Code')->willReturn($passCode);

        $this->tokenService->getToken(Uuid::fromString($token), $passCode)->willReturn($actualToken);

        $handler->handle($request)->willReturn($response);
        $this->process($request, $handler)->shouldReturn($response);
    }

    public function it_will_error_on_non_logged_in_user(
        ServerRequestInterface $request,
        UriInterface $uri,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    )
    {
        $request->getUri()->willReturn($uri);
        $uri->getPath()->willReturn('/admin');
        $request->getHeaderLine('Authentication-Token')->willReturn('');

        $this->process($request, $handler)->shouldHaveType(JsonApiErrorResponse::class);
    }

    public function it_allows_logged_in_user(
        ServerRequestInterface $request,
        UriInterface $uri,
        RequestHandlerInterface $handler,
        Token $actualToken,
        ResponseInterface $response
    )
    {
        $tokenUuid = Uuid::uuid4();
        $passCode = bin2hex(random_bytes(20)); 

        $request->getUri()->willReturn($uri);
        $uri->getPath()->willReturn('/test/private');
        $request->getHeaderLine('Accept')->willReturn('*/*');
        $request->getHeaderLine('Authentication-Token')->willReturn($tokenUuid->toString());
        $request->getHeaderLine('Authentication-Pass-Code')->willReturn($passCode);

        $this->tokenService->getToken($tokenUuid, $passCode)->willReturn($actualToken);

        $handler->handle($request)->willReturn($response);
        $this->process($request, $handler)->shouldReturn($response);
    }
}
