<?php declare(strict_types = 1);

namespace Spot\Auth;

use DateTimeInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Auth\Entity\Token;
use Spot\Auth\Entity\User;
use Spot\Auth\Exception\LoginFailedException;
use Spot\Auth\Repository\TokenRepository;
use Spot\DataModel\Repository\NoUniqueResultException;

class TokenService
{
    public function __construct(
        private TokenRepository $tokenRepository,
        private int $tokenMaxAge
    )
    {
    }

    public function createTokenForUser(User $user): Token
    {
        $token = new Token(Uuid::uuid4(), $this->generatePassCode(), $user->getUuid(), $this->generateExpires());
        $this->tokenRepository->create($token);
        return $token;
    }

    private function generateExpires(): DateTimeInterface
    {
        return new \DateTimeImmutable('+' . $this->tokenMaxAge . ' seconds');
    }

    private function generatePassCode(): string
    {
        return bin2hex(random_bytes(20));
    }

    public function getToken(UuidInterface $uuid, string $passCode): Token
    {
        try {
            $token = $this->tokenRepository->getByUuid($uuid);
        } catch (NoUniqueResultException $exception) {
            throw LoginFailedException::invalidToken($exception);
        }

        if (!hash_equals($token->getPassCode(), $passCode)) {
            throw LoginFailedException::invalidCredentials();
        }
        if ($token->getExpires() < new \DateTimeImmutable()) {
            throw LoginFailedException::invalidToken();
        }
        return $token;
    }

    public function refresh(Token $token): Token
    {
        $newToken = new Token(
            Uuid::uuid4(),
            $this->generatePassCode(),
            $token->getUserUuid(),
            $this->generateExpires()
        );
        $this->tokenRepository->create($newToken);
        $this->tokenRepository->delete($token);

        return $newToken;
    }

    public function remove(Token $token): void
    {
        $this->tokenRepository->delete($token);
    }

    public function invalidateExpiredTokens(): void
    {
        $this->tokenRepository->deleteExpired();
    }
}
