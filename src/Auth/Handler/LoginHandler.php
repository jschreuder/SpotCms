<?php declare(strict_types = 1);

namespace Spot\Auth\Handler;

use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
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
use Spot\Auth\AuthenticationService;
use Zend\Diactoros\Response\JsonResponse;

class LoginHandler implements HttpRequestParserInterface, ExecutorInterface, GeneratorInterface
{
    use LoggableTrait;

    const MESSAGE = 'login';

    /** @var  AuthenticationService */
    private $authenticationService;

    public function __construct(AuthenticationService $authenticationService, LoggerInterface $logger)
    {
        $this->authenticationService = $authenticationService;
        $this->logger = $logger;
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

            return new Response(self::MESSAGE, [
                'token' => $token->getUuid()->toString(),
                'pass_code' => $token->getPassCode(),
                'expires'=> $token->getExpires()->format('Y-m-d H:i:s'),
            ], $request);
        } catch (AuthException $exception) {
            return new Response($exception->getMessage(), [], $request);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException(
                'An error occurred during LoginHandler.',
                new ServerErrorResponse([], $request)
            );
        }
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
