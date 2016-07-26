<?php

namespace spec\Spot\Auth\Entity;

use PhpSpec\ObjectBehavior;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Auth\Entity\User;
use Spot\Auth\Value\EmailAddress;

/** @mixin  User */
class UserSpec extends ObjectBehavior
{
    /** @var  UuidInterface */
    private $userUuid;

    /** @var  EmailAddress */
    private $emailAddress;

    /** @var  string */
    private $password;

    /** @var  string */
    private $displayName;

    public function let()
    {
        $this->userUuid = Uuid::uuid4();
        $this->emailAddress = EmailAddress::get('darth@vader.empire');
        $this->password = password_hash('luke.is.my.son', PASSWORD_BCRYPT);
        $this->displayName = 'KittenLover1977';
        $this->beConstructedWith($this->userUuid, $this->emailAddress, $this->password, $this->displayName);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(User::class);
    }

    public function it_has_a_uuid()
    {
        $this->getUuid()->shouldReturn($this->userUuid);
    }

    public function it_has_and_can_change_email_address()
    {
        $this->getEmailAddress()->shouldReturn($this->emailAddress);

        $newEmail = EmailAddress::get('anakin@skywalker.jedi');
        $this->setEmailAddress($newEmail)->shouldReturn($this);
        $this->getEmailAddress()->shouldReturn($newEmail);
    }

    public function it_has_and_can_change_its_password()
    {
        $this->getPassword()->shouldReturn($this->password);

        $newPassword = password_hash('ben.is.my.buddy', PASSWORD_BCRYPT);
        $this->setPassword($newPassword)->shouldReturn($this);
        $this->getPassword()->shouldReturn($newPassword);
    }

    public function it_has_and_can_change_its_display_name()
    {
        $this->getDisplayName()->shouldReturn($this->displayName);

        $newDisplayName = 'Anakin Skywalker';
        $this->setDisplayName($newDisplayName)->shouldReturn($this);
        $this->getDisplayName()->shouldReturn($newDisplayName);
    }
}
