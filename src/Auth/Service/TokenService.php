<?php declare(strict_types = 1);

namespace Spot\Auth\Service;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Auth\Entity\Token;
use Spot\Auth\Entity\User;
use Spot\Auth\Repository\TokenRepository;

class TokenService
{
    /** @var  TokenRepository */
    private $tokenRepository;

    /** @var  int */
    private $tokenMaxAge;

    public function __construct(TokenRepository $tokenRepository, int $tokenMaxAge)
    {
        $this->tokenRepository = $tokenRepository;
        $this->tokenMaxAge = $tokenMaxAge;
    }

    public function createTokenForUser(User $user)
    {
        $token = new Token(Uuid::uuid4(), $this->generatePassCode(), $user->getUuid(), $this->generateExpires());
        $this->tokenRepository->create($token);
        return $token;
    }

    private function generateExpires() : \DateTimeInterface
    {
        return new \DateTimeImmutable('+' . $this->tokenMaxAge . ' seconds');
    }

    private function generatePassCode() : string
    {
        return bin2hex(random_bytes(20));
    }

    public function getToken(UuidInterface $uuid, string $passCode) : Token
    {
        $token = $this->tokenRepository->getByUuid($uuid);
        if (!hash_equals($token->getPassCode(), $passCode)) {
            throw new \RuntimeException('Invalid token');
        }
        if ($token->getExpires() < new \DateTimeImmutable()) {
            throw new \RuntimeException('Expired token');
        }
        return $token;
    }

    public function refresh(Token $token) : Token
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

    public function remove(Token $token)
    {
        $this->tokenRepository->delete($token);
    }

    public function invalidateExpiredTokens()
    {
        $this->tokenRepository->deleteExpired();
    }
}
