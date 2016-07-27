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
        $expires = new \DateTimeImmutable('+' . $this->tokenMaxAge . ' seconds');
        $token = new Token(Uuid::uuid4(), bin2hex(random_bytes(20)), $user->getUuid(), $expires);
        $this->tokenRepository->create($token);
        return $token;
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

    public function remove(Token $token)
    {
        $this->tokenRepository->delete($token);
    }

    public function invalidateExpiredTokens()
    {
        $this->tokenRepository->deleteExpired();
    }
}
