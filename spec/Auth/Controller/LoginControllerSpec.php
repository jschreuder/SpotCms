<?php

namespace spec\Spot\Auth\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Spot\Auth\Entity\Token;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\Controller\LoginController;
use Spot\Auth\AuthenticationService;

/** @mixin  LoginController */
class LoginControllerSpec extends ObjectBehavior
{
    /** @var  AuthenticationService */
    private $authenticationService;

    public function let(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
        $this->beConstructedWith($authenticationService);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(LoginController::class);
    }

    public function it_can_validate_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $httpRequest->getParsedBody()->willReturn([
            'data' => [
                'type' => 'users',
                'id' => 'bb@eight.poe.com',
                'attributes' => [
                    'password' => 'not.damerons.coat',
                ],
            ],
        ]);

        $this->validateRequest($httpRequest);
    }

    public function it_errors_on_invalid_request(ServerRequestInterface $httpRequest)
    {
        $httpRequest->getParsedBody()->willReturn([]);
        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($httpRequest);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, Token $token)
    {
        $emailAddress = 'bb@eight.poe';
        $password = 'not.damerons.coat';

        $request->getParsedBody()->willReturn([
            'data' => [
                'type' => 'users',
                'id' => $emailAddress,
                'attributes' => [
                    'password' => $password,
                ],
            ],
        ]);

        $this->authenticationService->login($emailAddress, $password)->willReturn($token);

        $tokenUuid = Uuid::uuid4();
        $passCode = bin2hex(random_bytes(20));
        $expires = new \DateTimeImmutable('+42 seconds');
        $token->getUuid()->willReturn($tokenUuid);
        $token->getPassCode()->willReturn($passCode);
        $token->getExpires()->willReturn($expires);

        $response = $this->execute($request);
        $response->shouldHaveType(ResponseInterface::class);

        $body = $response->getBody()->getContents();
        $body->shouldContain($tokenUuid->toString());
        $body->shouldContain($passCode);
        $body->shouldContain($expires->format('Y-m-d H:i:s'));
    }

    public function it_can_handle_auth_errors_when_executing_a_request(ServerRequestInterface $request)
    {
        $emailAddress = 'bb@eight.poe';
        $password = 'not.damerons.coat';

        $request->getParsedBody()->willReturn([
            'data' => [
                'type' => 'users',
                'id' => $emailAddress,
                'attributes' => [
                    'password' => $password,
                ],
            ],
        ]);

        $error = 'test';
        $this->authenticationService->login($emailAddress, $password)->willThrow(new AuthException($error, 500));

        $response = $this->execute($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getStatusCode()->shouldBe(401);
    }
}
