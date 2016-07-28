<?php

namespace spec\Spot\Auth\Handler;

use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\Auth\Entity\Token;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\Handler\LoginHandler;
use Spot\Auth\Service\AuthenticationService;

/** @mixin  LoginHandler */
class LoginHandlerSpec extends ObjectBehavior
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
        $this->shouldHaveType(LoginHandler::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $emailAddress = 'bb@eight.poe';
        $password = 'not.damerons.coat';

        $httpRequest->getHeaderLine('Accept')->shouldBeCalled();
        $httpRequest->getParsedBody()->willReturn([
            'data' => [
                'type' => 'users',
                'id' => $emailAddress,
                'attributes' => [
                    'password' => $password,
                ],
            ],
        ]);

        $request = $this->parseHttpRequest($httpRequest, []);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(LoginHandler::MESSAGE);
        $request['email_address']->shouldBe($emailAddress);
        $request['password']->shouldBe($password);
    }

    public function it_errors_on_invalid_path_when_parsing_request(ServerRequestInterface $httpRequest)
    {
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, []);
    }

    public function it_can_execute_a_request(RequestInterface $request, Token $token)
    {
        $emailAddress = 'bb@eight.poe';
        $password = 'not.damerons.coat';

        $request->getAcceptContentType()->willReturn('*/*');
        $request->offsetGet('email_address')->willReturn($emailAddress);
        $request->offsetGet('password')->willReturn($password);

        $this->authenticationService->login($emailAddress, $password)->willReturn($token);

        $tokenUuid = Uuid::uuid4();
        $passCode = bin2hex(random_bytes(20));
        $expires = new \DateTimeImmutable('+42 seconds');
        $token->getUuid()->willReturn($tokenUuid);
        $token->getPassCode()->willReturn($passCode);
        $token->getExpires()->willReturn($expires);

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn(LoginHandler::MESSAGE);
        $response['token']->shouldBe($tokenUuid->toString());
        $response['pass_code']->shouldBe($passCode);
        $response['expires']->shouldBe($expires->format('Y-m-d H:i:s'));
    }

    public function it_can_handle_errors_when_executing_a_request(RequestInterface $request)
    {
        $emailAddress = 'bb@eight.poe';
        $password = 'not.damerons.coat';

        $request->getAcceptContentType()->willReturn('*/*');
        $request->offsetGet('email_address')->willReturn($emailAddress);
        $request->offsetGet('password')->willReturn($password);

        $error = 'test';
        $this->authenticationService->login($emailAddress, $password)->willThrow(new AuthException($error, 500));

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn($error);
    }

    public function it_can_generate_a_response(ResponseInterface $response)
    {
        $tokenUuid = Uuid::uuid4()->toString();
        $passCode = bin2hex(random_bytes(20));
        $expires = (new \DateTimeImmutable('+42 seconds'))->format('Y-m-d H:i:s');
        $response->getResponseName()->willReturn(LoginHandler::MESSAGE);
        $response->offsetGet('token')->willReturn($tokenUuid);
        $response->offsetGet('pass_code')->willReturn($passCode);
        $response->offsetGet('expires')->willReturn($expires);

        $httpResponse = $this->generateResponse($response);
        $httpResponse->shouldHaveType(\Psr\Http\Message\ResponseInterface::class);
    }
}
