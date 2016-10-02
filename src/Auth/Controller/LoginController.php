<?php declare(strict_types = 1);

namespace Spot\Auth\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Controller\ValidationFailedException;
use Particle\Validator\Validator;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Ramsey\Uuid\UuidInterface;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\AuthenticationService;
use Zend\Diactoros\Response\JsonResponse;

class LoginController implements RequestValidatorInterface, ControllerInterface
{
    /** @var  AuthenticationService */
    private $authenticationService;

    public function __construct(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    public function validateRequest(ServerHttpRequest $request)
    {
        $validator = new Validator();
        $validator->required('data.type')->equals('users');
        $validator->required('data.id')->email();
        $validator->required('data.attributes.password');

        $result = $validator->validate($request->getParsedBody());
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }

    public function execute(ServerHttpRequest $request) : HttpResponse
    {
        $data = $request->getParsedBody()['data'];

        try {
            $token = $this->authenticationService->login($data['id'], $data['attributes']['password']);
            return $this->generateResponse($token->getUuid(), $token->getPassCode(), $token->getExpires());
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
