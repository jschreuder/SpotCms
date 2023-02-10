<?php

namespace spec\Spot\Auth\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
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

    public function it_can_validate_a_HttpRequest(ServerRequestInterface $httpRequest, ServerRequestInterface $httpRequest2)
    {
        $token = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));
        $data = [
            'token' => $token,
            'pass_code' => $passCode,
        ];

        $httpRequest->getHeaderLine('Authentication-Token')->willReturn($token);
        $httpRequest->getHeaderLine('Authentication-Pass-Code')->willReturn($passCode);

        $httpRequest->withParsedBody($data)->willReturn($httpRequest2);
        $httpRequest2->getParsedBody()->willReturn($data);

        $this->validateRequest($httpRequest);
    }

    public function it_errors_on_invalid_request(ServerRequestInterface $request, ServerRequestInterface $request2)
    {
        $data = [
            'token' => null,
            'pass_code' => null,
        ];

        $request->getHeaderLine('Authentication-Token')->willReturn(null);
        $request->getHeaderLine('Authentication-Pass-Code')->willReturn(null);

        $request->withParsedBody($data)->willReturn($request2);
        $request2->getParsedBody()->willReturn($data);

        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, ServerRequestInterface $request2, Token $currentToken)
    {
        $token = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));
        $data = [
            'token' => $token,
            'pass_code' => $passCode,
        ];

        $request->getHeaderLine('Authentication-Token')->willReturn($token);
        $request->getHeaderLine('Authentication-Pass-Code')->willReturn($passCode);

        $request->withParsedBody($data)->willReturn($request2);
        $request2->getParsedBody()->willReturn($data);

        $this->tokenService->getToken(Uuid::fromString($token), $passCode)->willReturn($currentToken);
        $this->tokenService->remove($currentToken)->shouldBeCalled();

        $response = $this->execute($request);
        $response->shouldHaveType(ResponseInterface::class);
    }

    public function it_can_handle_auth_errors_when_executing_a_request(ServerRequestInterface $request, ServerRequestInterface $request2)
    {
        $token = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));
        $data = [
            'token' => $token,
            'pass_code' => $passCode,
        ];

        $request->getHeaderLine('Authentication-Token')->willReturn($token);
        $request->getHeaderLine('Authentication-Pass-Code')->willReturn($passCode);

        $request->withParsedBody($data)->willReturn($request2);
        $request2->getParsedBody()->willReturn($data);

        $error = 'test';
        $this->tokenService->getToken(Uuid::fromString($token), $passCode)
            ->willThrow(new AuthException($error, 500));

        $response = $this->execute($request);
        $response->shouldHaveType(ResponseInterface::class);
    }
}
