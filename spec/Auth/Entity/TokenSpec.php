<?php

namespace spec\Spot\Auth\Entity;

use PhpSpec\ObjectBehavior;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Auth\Entity\Token;

/** @mixin  Token */
class TokenSpec extends ObjectBehavior
{
    /** @var  UuidInterface */
    private $tokenUuid;

    /** @var  string */
    private $passCode;

    /** @var  UuidInterface */
    private $userUuid;

    /** @var  \DateTimeInterface */
    private $expires;

    public function let()
    {
        $this->tokenUuid = Uuid::uuid4();
        $this->passCode = bin2hex(random_bytes(20));
        $this->userUuid = Uuid::uuid4();
        $this->expires = new \DateTimeImmutable('+3600 seconds');
        $this->beConstructedWith($this->tokenUuid, $this->passCode, $this->userUuid, $this->expires);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Token::class);
    }

    public function it_allows_access_to_its_properties()
    {
        $this->getUuid()->shouldReturn($this->tokenUuid);
        $this->getPassCode()->shouldReturn($this->passCode);
        $this->getUserUuid()->shouldReturn($this->userUuid);
        $this->getExpires()->shouldReturn($this->expires);
    }
}
