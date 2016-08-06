<?php declare(strict_types = 1);

namespace Spot\Auth;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Api\LoggableTrait;
use Spot\Auth\Entity\Token;
use Spot\Auth\Entity\User;
use Spot\Auth\Exception\AuthException;
use Spot\Auth\Exception\LoginFailedException;
use Spot\Auth\Repository\UserRepository;
use Spot\Auth\Value\EmailAddress;
use Spot\DataModel\Repository\NoUniqueResultException;

class AuthenticationService
{
    use LoggableTrait;

    /** @var  UserRepository */
    private $userRepository;

    /** @var  TokenService */
    private $tokenService;

    /** @var  int */
    private $algorithm = PASSWORD_BCRYPT;

    /** @var  LoggerInterface */
    private $logger;

    /** @var  array */
    private $passwordOptions = [
        'cost' => 10,
    ];

    public function __construct(UserRepository $userRepository, TokenService $tokenService, LoggerInterface $logger)
    {
        $this->userRepository = $userRepository;
        $this->tokenService = $tokenService;
        $this->logger = $logger;
    }

    public function createUser(EmailAddress $emailAddress, string $password, string $displayName) : User
    {
        $user = new User(
            Uuid::uuid4(),
            $emailAddress,
            password_hash($password, $this->algorithm, $this->passwordOptions),
            $displayName
        );
        $this->userRepository->create($user);
        return $user;
    }

    public function login(string $email, string $password) : Token
    {
        try {
            $user = $this->getUserByEmail($email);
            $this->verifyPassword($user, $password);

            return $this->tokenService->createTokenForUser($user);
        } catch (\Throwable $exception) {
            if ($exception instanceof AuthException) {
                throw $exception;
            }
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw LoginFailedException::systemError($exception);
        }
    }

    private function getUserByEmail(string $email) : User
    {
        try {
            $emailAddress = EmailAddress::get($email);
            return $this->userRepository->getByEmailAddress($emailAddress);
        } catch (NoUniqueResultException $exception) {
            throw LoginFailedException::invalidCredentials($exception);
        } catch (\InvalidArgumentException $exception) {
            throw LoginFailedException::invalidEmailAddress();
        }
    }

    private function verifyPassword(User $user, string $password)
    {
        if (!password_verify($password, $user->getPassword())) {
            throw LoginFailedException::invalidCredentials();
        }

        if (password_needs_rehash($user->getPassword(), $this->algorithm, $this->passwordOptions)) {
            $user->setPassword(password_hash($password, $this->algorithm, $this->passwordOptions));
            $this->userRepository->update($user);
        }
    }

    public function getUserForToken(UuidInterface $tokenUuid, string $passCode) : User
    {
        try {
            try {
                $token = $this->tokenService->getToken($tokenUuid, $passCode);
            } catch (NoUniqueResultException $exception) {
                throw LoginFailedException::invalidToken($exception);
            }
            return $this->userRepository->getByUuid($token->getUserUuid());
        } catch (\Throwable $exception) {
            if ($exception instanceof AuthException) {
                throw $exception;
            }
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw LoginFailedException::systemError($exception);
        }
    }

    public function logout(Token $token)
    {
        $this->tokenService->remove($token);
    }
}
