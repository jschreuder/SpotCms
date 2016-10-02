<?php

namespace spec\Spot\Auth\Handler;

use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\Auth\Entity\Token;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\Controller\RefreshTokenController;
use Spot\Auth\TokenService;

/** @mixin  RefreshTokenController */
class RefreshTokenHandlerSpec extends ObjectBehavior
{
    /** @var  TokenService */
    private $tokenService;

    /** @var  LoggerInterface */
    private $logger;

    public function let(TokenService $tokenService, LoggerInterface $logger)
    {
        $this->tokenService = $tokenService;
        $this->logger = $logger;
        $this->beConstructedWith($tokenService, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RefreshTokenController::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $token = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));

        $httpRequest->getHeaderLine('Accept')->shouldBeCalled();
        $httpRequest->getHeaderLine('Authentication-Token')->willReturn($token);
        $httpRequest->getHeaderLine('Authentication-Pass-Code')->willReturn($passCode);

        $request = $this->parseHttpRequest($httpRequest, []);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(RefreshTokenController::MESSAGE);
        $request['token']->shouldBe($token);
        $request['pass_code']->shouldBe($passCode);
    }

    public function it_errors_on_invalid_path_when_parsing_request(ServerRequestInterface $httpRequest)
    {
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, []);
    }

    public function it_can_execute_a_request(RequestInterface $request, Token $oldToken, Token $newToken)
    {
        $tokenUuid = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));

        $request->getAcceptContentType()->willReturn('*/*');
        $request->offsetGet('token')->willReturn($tokenUuid);
        $request->offsetGet('pass_code')->willReturn($passCode);

        $this->tokenService->getToken(Uuid::fromString($tokenUuid), $passCode)->willReturn($oldToken);
        $this->tokenService->refresh($oldToken)->willReturn($newToken);

        $newUuid = Uuid::uuid4();
        $newPassCode = bin2hex(random_bytes(20));
        $expires = new \DateTimeImmutable('+42 seconds');
        $newToken->getUuid()->willReturn($newUuid);
        $newToken->getPassCode()->willReturn($newPassCode);
        $newToken->getExpires()->willReturn($expires);

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn(RefreshTokenController::MESSAGE);
        $response['token']->shouldBe($newUuid->toString());
        $response['pass_code']->shouldBe($newPassCode);
        $response['expires']->shouldBe($expires->format('Y-m-d H:i:s'));
    }

    public function it_can_handle_auth_errors_when_executing_a_request(RequestInterface $request)
    {
        $tokenUuid = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));

        $request->getAcceptContentType()->willReturn('*/*');
        $request->offsetGet('token')->willReturn($tokenUuid);
        $request->offsetGet('pass_code')->willReturn($passCode);

        $error = 'test';
        $this->tokenService->getToken(Uuid::fromString($tokenUuid), $passCode)
            ->willThrow(new AuthException($error, 500));

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn($error);
    }

    public function it_can_handle_errors_when_executing_a_request(RequestInterface $request)
    {
        $tokenUuid = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));

        $request->getAcceptContentType()->willReturn('*/*');
        $request->offsetGet('token')->willReturn($tokenUuid);
        $request->offsetGet('pass_code')->willReturn($passCode);

        $this->tokenService->getToken(Uuid::fromString($tokenUuid), $passCode)
            ->willThrow(new \RuntimeException());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }

    public function it_can_generate_a_response(ResponseInterface $response)
    {
        $tokenUuid = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));
        $expires = (new \DateTimeImmutable('+42 seconds'))->format('Y-m-d H:i:s');
        $response->getResponseName()->willReturn(RefreshTokenController::MESSAGE);
        $response->offsetGet('token')->willReturn($tokenUuid);
        $response->offsetGet('pass_code')->willReturn($passCode);
        $response->offsetGet('expires')->willReturn($expires);

        $httpResponse = $this->generateResponse($response);
        $httpResponse->shouldHaveType(\Psr\Http\Message\ResponseInterface::class);
    }
}
