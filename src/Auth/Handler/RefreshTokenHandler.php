<?php declare(strict_types = 1);

namespace Spot\Auth\Handler;

use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Generator\GeneratorInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\HttpRequestParserHelper;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\Service\TokenService;
use Zend\Diactoros\Response\JsonResponse;

class RefreshTokenHandler implements HttpRequestParserInterface, ExecutorInterface, GeneratorInterface
{
    const MESSAGE = 'token.refresh';

    /** @var  TokenService */
    private $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $rpHelper = new HttpRequestParserHelper($httpRequest);

        $validator = $rpHelper->getValidator();
        $validator->required('token')->uuid();
        $validator->required('pass_code')->length(40);

        return new Request(self::MESSAGE, $rpHelper->filterAndValidate([
            'token' => $httpRequest->getHeaderLine('Authentication-Token'),
            'pass_code' => $httpRequest->getHeaderLine('Authentication-Pass-Code'),
        ]), $httpRequest);
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $token = $this->tokenService->getToken(Uuid::fromString($request['token']), $request['pass_code']);
            $newToken = $this->tokenService->refresh($token);
        } catch (AuthException $exception) {
            return new Response($exception->getMessage(), [], $request);
        }

        return new Response(self::MESSAGE, [
            'token' => $newToken->getUuid()->toString(),
            'pass_code' => $newToken->getPassCode(),
            'expires'=> $newToken->getExpires()->format('Y-m-d H:i:s'),
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
