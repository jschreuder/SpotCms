<?php declare(strict_types = 1);

namespace Spot\Auth\Entity;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class Token
{
    public function __construct(
        private UuidInterface $tokenUuid,
        private string $passCode,
        private UuidInterface $userUuid,
        private DateTimeInterface $expires
    )
    {
        $this->passCode = substr($passCode, 0, 40);
    }

    public function getUuid() : UuidInterface
    {
        return $this->tokenUuid;
    }

    public function getPassCode(): string
    {
        return $this->passCode;
    }

    public function getUserUuid(): UuidInterface
    {
        return $this->userUuid;
    }

    public function getExpires(): DateTimeInterface
    {
        return $this->expires;
    }
}
