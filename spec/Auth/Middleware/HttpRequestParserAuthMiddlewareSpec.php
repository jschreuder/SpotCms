<?php

namespace spec\Spot\Auth\Middleware;

use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\UnauthorizedRequest;
use Spot\Api\Request\RequestInterface;
use Spot\Auth\Entity\Token;
use Spot\Auth\Middleware\HttpRequestParserAuthMiddleware;
use Spot\Auth\AuthenticationService;
use Spot\Auth\TokenService;

/** @mixin  HttpRequestParserAuthMiddleware */
class HttpRequestParserAuthMiddlewareSpec extends ObjectBehavior
{
    /** @var  HttpRequestParserInterface */
    private $httpRequestParser;

    /** @var  TokenService */
    private $tokenService;

    /** @var  AuthenticationService */
    private $authenticationService;

    private $publicMessageName = 'test.public';

    /** @var  LoggerInterface */
    private $logger;

    public function let(
        HttpRequestParserInterface $httpRequestParser,
        TokenService $tokenService,
        AuthenticationService $authenticationService,
        LoggerInterface $logger
    )
    {
        $this->httpRequestParser = $httpRequestParser;
        $this->tokenService = $tokenService;
        $this->authenticationService = $authenticationService;
        $this->logger = $logger;
        $this->beConstructedWith(
            $httpRequestParser,
            $tokenService,
            $authenticationService,
            [$this->publicMessageName],
            $logger
        );
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(HttpRequestParserAuthMiddleware::class);
    }

    public function it_can_parse_a_http_request(ServerHttpRequest $httpRequest, RequestInterface $request)
    {
        $this->httpRequestParser->parseHttpRequest($httpRequest, [])->willReturn($request);
        $request->getRequestName()->willReturn($this->publicMessageName);
        $this->parseHttpRequest($httpRequest, [])->shouldReturn($request);
    }

    public function it_will_error_on_non_logged_in_user(ServerHttpRequest $httpRequest, RequestInterface $request)
    {
        $this->httpRequestParser->parseHttpRequest($httpRequest, [])->willReturn($request);
        $request->getRequestName()->willReturn($messageName = 'test.private');
        $httpRequest->getHeaderLine('Accept')->willReturn('*/*');
        $httpRequest->getHeaderLine('Authentication-Token')->willReturn('');

        $errorRequest = $this->parseHttpRequest($httpRequest, []);
        $errorRequest->shouldHaveType(UnauthorizedRequest::class);
    }

    public function it_allows_logged_in_user(ServerHttpRequest $httpRequest, RequestInterface $request, Token $token)
    {
        $tokenUuid = Uuid::uuid4();
        $passCode = bin2hex(random_bytes(20));

        $this->httpRequestParser->parseHttpRequest($httpRequest, [])->willReturn($request);
        $request->getRequestName()->willReturn($messageName = 'test.private');
        $httpRequest->getHeaderLine('Accept')->willReturn('*/*');
        $httpRequest->getHeaderLine('Authentication-Token')->willReturn($tokenUuid->toString());
        $httpRequest->getHeaderLine('Authentication-Pass-Code')->willReturn($passCode);

        $this->tokenService->getToken($tokenUuid, $passCode)->willReturn($token);

        $this->parseHttpRequest($httpRequest, [])->shouldReturn($request);
    }
}
