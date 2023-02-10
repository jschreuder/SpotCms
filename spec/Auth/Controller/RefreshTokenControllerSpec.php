<?php

namespace spec\Spot\Auth\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Spot\Auth\Entity\Token;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\Controller\RefreshTokenController;
use Spot\Auth\TokenService;

/** @mixin  RefreshTokenController */
class RefreshTokenControllerSpec extends ObjectBehavior
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
        $this->shouldHaveType(RefreshTokenController::class);
    }

    public function it_can_validate_a_HttpRequest(ServerRequestInterface $request, ServerRequestInterface $request2)
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

        $this->validateRequest($request);
    }

    public function it_errors_on_invalid_path_when_parsing_request(ServerRequestInterface $request, ServerRequestInterface $request2)
    {
        $data = [
            'token' => null,
            'pass_code' => null,
        ];

        $request->getHeaderLine('Authentication-Token')->willReturn(null);
        $request->getHeaderLine('Authentication-Pass-Code')->willReturn(null);

        $request->withParsedBody($data)->willReturn($request2);
        $request2->getParsedBody()->willReturn($data);

        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request, []);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, ServerRequestInterface $request2, Token $oldToken, Token $newToken)
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

        $this->tokenService->getToken(Uuid::fromString($token), $passCode)->willReturn($oldToken);
        $this->tokenService->refresh($oldToken)->willReturn($newToken);

        $newUuid = Uuid::uuid4();
        $newPassCode = bin2hex(random_bytes(20));
        $expires = new \DateTimeImmutable('+42 seconds');
        $newToken->getUuid()->willReturn($newUuid);
        $newToken->getPassCode()->willReturn($newPassCode);
        $newToken->getExpires()->willReturn($expires);

        $response = $this->execute($request);

        $body = $response->getBody()->getContents();
        $body->shouldContain($newUuid->toString());
        $body->shouldContain($newPassCode);
        $body->shouldContain($expires->format('Y-m-d H:i:s'));
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
        $response->getStatusCode()->shouldBe(401);
    }
}
