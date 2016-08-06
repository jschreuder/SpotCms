<?php declare(strict_types = 1);

namespace Spot\Auth\Handler;

use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Generator\GeneratorInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\HttpRequestParserHelper;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\TokenService;
use Zend\Diactoros\Response\JsonResponse;

class LogoutHandler implements HttpRequestParserInterface, ExecutorInterface, GeneratorInterface
{
    use LoggableTrait;

    const MESSAGE = 'token.delete';

    /** @var  TokenService */
    private $tokenService;

    public function __construct(TokenService $tokenService, LoggerInterface $logger)
    {
        $this->tokenService = $tokenService;
        $this->logger = $logger;
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
            $this->tokenService->remove($token);

            return new Response(self::MESSAGE, [], $request);
        } catch (AuthException $exception) {
            return new Response($exception->getMessage(), [], $request);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException(
                'An error occurred during LogoutHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }

    public function generateResponse(ResponseInterface $response) : HttpResponse
    {
        return new JsonResponse(['data' => []], 200);
    }
}
