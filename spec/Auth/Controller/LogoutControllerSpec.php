<?php

namespace spec\Spot\Auth\Controller;

use jschreuder\Middle\Controller\ValidationFailedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PhpSpec\ObjectBehavior;
use Ramsey\Uuid\Uuid;
use Spot\Auth\Entity\Token;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\Controller\LogoutController;
use Spot\Auth\TokenService;

/** @mixin  LogoutController */
class LogoutControllerSpec extends ObjectBehavior
{
    /** @var  TokenService */
    private $tokenService;

    public function let(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
        $this->beConstructedWith($tokenService);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(LogoutController::class);
    }

    public function it_can_validate_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $token = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));

        $httpRequest->getHeaderLine('Authentication-Token')->willReturn($token);
        $httpRequest->getHeaderLine('Authentication-Pass-Code')->willReturn($passCode);

        $this->validateRequest($httpRequest);
    }

    public function it_errors_on_invalid_request(ServerRequestInterface $httpRequest)
    {
        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($httpRequest);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, Token $token)
    {
        $tokenUuid = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));

        $request->getHeaderLine('Authentication-Token')->willReturn($tokenUuid);
        $request->getHeaderLine('Authentication-Pass-Code')->willReturn($passCode);

        $this->tokenService->getToken(Uuid::fromString($tokenUuid), $passCode)->willReturn($token);
        $this->tokenService->remove($token)->shouldBeCalled();

        $response = $this->execute($request);
        $response->shouldHaveType(ResponseInterface::class);
    }

    public function it_can_handle_auth_errors_when_executing_a_request(ServerRequestInterface $request)
    {
        $tokenUuid = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));

        $request->getHeaderLine('Authentication-Token')->willReturn($tokenUuid);
        $request->getHeaderLine('Authentication-Pass-Code')->willReturn($passCode);

        $error = 'test';
        $this->tokenService->getToken(Uuid::fromString($tokenUuid), $passCode)
            ->willThrow(new AuthException($error, 500));

        $response = $this->execute($request);
        $response->shouldHaveType(ResponseInterface::class);
    }
}
