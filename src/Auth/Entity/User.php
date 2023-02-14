<?php declare(strict_types = 1);

namespace Spot\Auth\Entity;

use Ramsey\Uuid\UuidInterface;
use Spot\Auth\Value\EmailAddress;
use Spot\DataModel\Entity\TimestampedMetaDataTrait;

class User
{
    use TimestampedMetaDataTrait;

    const TYPE = 'users';

    public function __construct(
        private UuidInterface $userUuid,
        private EmailAddress $emailAddress,
        private string $password,
        private string $displayName
    )
    {
    }

    public function getUuid(): UuidInterface
    {
        return $this->userUuid;
    }

    public function setEmailAddress(EmailAddress $emailAddress): self
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    public function getEmailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }
}
