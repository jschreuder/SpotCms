<?php declare(strict_types = 1);

namespace Spot\Auth\Entity;

use Ramsey\Uuid\UuidInterface;

class Token
{
    /** @var  UuidInterface */
    private $tokenUuid;

    /** @var  string */
    private $passCode;

    /** @var  UuidInterface */
    private $userUuid;

    /** @var  \DateTimeInterface */
    private $expires;

    public function __construct(
        UuidInterface $tokenUuid,
        string $passCode,
        UuidInterface $userUuid,
        \DateTimeInterface $expires
    )
    {
        $this->tokenUuid = $tokenUuid;
        $this->setPassCode($passCode);
        $this->userUuid = $userUuid;
        $this->expires = $expires;
    }

    public function getUuid() : UuidInterface
    {
        return $this->tokenUuid;
    }

    private function setPassCode(string $passCode)
    {
        $this->passCode = substr($passCode, 0, 40);
    }

    public function getPassCode(): string
    {
        return $this->passCode;
    }

    public function getUserUuid(): UuidInterface
    {
        return $this->userUuid;
    }

    public function getExpires(): \DateTimeInterface
    {
        return $this->expires;
    }
}
