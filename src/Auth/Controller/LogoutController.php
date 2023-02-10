<?php declare(strict_types = 1);

namespace Spot\Auth\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Validator\StringLength;
use Laminas\Validator\Uuid as UuidValidator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\ValidationService;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\TokenService;

class LogoutController implements RequestValidatorInterface, ControllerInterface
{
    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function validateRequest(ServerRequestInterface $request): void
    {
        $data = [
            'token' => $request->getHeaderLine('Authentication-Token'),
            'pass_code' => $request->getHeaderLine('Authentication-Pass-Code'),
        ];
        ValidationService::validate($request->withParsedBody($data), [
            'token' => new UuidValidator(),
            'pass_code' => new StringLength(['min' => 40, 'max' => 40]),
        ]);
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $token = $this->tokenService->getToken(
                Uuid::fromString($request->getHeaderLine('Authentication-Token')),
                $request->getHeaderLine('Authentication-Pass-Code')
            );
            $this->tokenService->remove($token);
            return new JsonResponse(['data' => []], 200);
        } catch (AuthException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 401);
        }
    }
}
