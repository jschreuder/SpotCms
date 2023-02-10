<?php declare(strict_types = 1);

namespace Spot\Auth\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\UuidInterface;
use Spot\Application\ValidationService;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\AuthenticationService;

class LoginController implements RequestValidatorInterface, ControllerInterface
{
    private AuthenticationService $authenticationService;

    public function __construct(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    public function validateRequest(ServerRequestInterface $request): void
    {
        ValidationService::validate($request, [
            'data.type' => new Identical('users'),
            'data.id' => new EmailAddress(),
            'data.attributes.password' => new NotEmpty(),
        ]);
    }

    public function execute(ServerHttpRequest $request): HttpResponse
    {
        $data = $request->getParsedBody()['data'];

        try {
            $token = $this->authenticationService->login($data['id'], $data['attributes']['password']);
            return $this->generateResponse($token->getUuid(), $token->getPassCode(), $token->getExpires());
        } catch (AuthException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 401);
        }
    }

    public function generateResponse(UuidInterface $uuid, string $passCode, \DateTimeInterface $expires): HttpResponse
    {
        return new JsonResponse([
            'data' => [
                'type' => 'tokens',
                'id' => $uuid->toString(),
                'attributes' => [
                    'pass_code' => $passCode,
                    'expires' => $expires->format('Y-m-d H:i:s'),
                ],
            ],
        ], 201);
    }
}
