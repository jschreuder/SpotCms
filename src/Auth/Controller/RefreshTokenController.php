<?php declare(strict_types = 1);

namespace Spot\Auth\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Controller\ValidationFailedException;
use Particle\Validator\Validator;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\TokenService;
use Zend\Diactoros\Response\JsonResponse;

class RefreshTokenController implements RequestValidatorInterface, ControllerInterface
{
    /** @var  TokenService */
    private $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function validateRequest(ServerHttpRequest $request)
    {
        $validator = new Validator();
        $validator->required('token')->uuid();
        $validator->required('pass_code')->length(40);

        $data = [
            'token' => $request->getHeaderLine('Authentication-Token'),
            'pass_code' => $request->getHeaderLine('Authentication-Pass-Code'),
        ];
        $result = $validator->validate($data);
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }

    public function execute(ServerHttpRequest $request) : HttpResponse
    {
        try {
            $token = $this->tokenService->getToken(
                Uuid::fromString($request->getHeaderLine('Authentication-Token')),
                $request->getHeaderLine('Authentication-Pass-Code')
            );
            $newToken = $this->tokenService->refresh($token);

            return $this->generateResponse($newToken->getUuid(), $newToken->getPassCode(), $newToken->getExpires());
        } catch (AuthException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 401);
        }
    }

    public function generateResponse(UuidInterface $uuid, string $passCode, \DateTimeInterface $expires) : HttpResponse
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
