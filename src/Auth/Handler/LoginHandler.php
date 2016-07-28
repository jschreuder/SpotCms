<?php declare(strict_types = 1);

namespace Spot\Auth\Handler;

use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Generator\GeneratorInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\HttpRequestParserHelper;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\Service\AuthenticationService;
use Zend\Diactoros\Response\JsonResponse;

class LoginHandler implements HttpRequestParserInterface, ExecutorInterface, GeneratorInterface
{
    const MESSAGE = 'login';

    /** @var  AuthenticationService */
    private $authenticationService;

    public function __construct(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $rpHelper = new HttpRequestParserHelper($httpRequest);

        $validator = $rpHelper->getValidator();
        $validator->required('data.type')->equals('users');
        $validator->required('data.id')->email();
        $validator->required('data.attributes.password');

        $data = $rpHelper->filterAndValidate((array) $httpRequest->getParsedBody())['data'];
        return new Request(
            self::MESSAGE,
            [
                'email_address' => $data['id'],
                'password' => $data['attributes']['password'],
            ],
            $httpRequest
        );
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $token = $this->authenticationService->login($request['email_address'], $request['password']);
        } catch (AuthException $exception) {
            return new Response($exception->getMessage(), [], $request);
        }

        return new Response(self::MESSAGE, [
            'token' => $token->getUuid()->toString(),
            'pass_code' => $token->getPassCode(),
            'expires'=> $token->getExpires()->format('Y-m-d H:i:s'),
        ], $request);
    }

    public function generateResponse(ResponseInterface $response) : HttpResponse
    {
        return new JsonResponse([
            'data' => [
                'type' => 'tokens',
                'id' => $response['token'],
                'attributes' => [
                    'pass_code' => $response['pass_code'],
                    'expires' => $response['expires'],
                ],
            ],
        ], 201);
    }
}
