<?php declare(strict_types = 1);

namespace Spot\Auth\Entity;

use Ramsey\Uuid\UuidInterface;
use Spot\Auth\Value\EmailAddress;
use Spot\DataModel\Entity\TimestampedMetaDataTrait;

class User
{
    use TimestampedMetaDataTrait;

    const TYPE = 'users';

    /** @var  UuidInterface */
    private $userUuid;

    /** @var  EmailAddress */
    private $emailAddress;

    /** @var  string */
    private $password;

    /** @var  string */
    private $displayName;

    public function __construct(UuidInterface $userUuid, EmailAddress $email, string $password, string $displayName)
    {
        $this->userUuid = $userUuid;
        $this->setEmailAddress($email);
        $this->setPassword($password);
        $this->setDisplayName($displayName);
    }

    public function getUuid(): UuidInterface
    {
        return $this->userUuid;
    }

    public function setEmailAddress(EmailAddress $emailAddress) : self
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    public function getEmailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }

    public function setPassword(string $password) : self
    {
        $this->password = $password;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setDisplayName(string $displayName) : self
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }
}
