<?php

namespace spec\Spot\Auth\Service;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Spot\Auth\Entity\Token;
use Spot\Auth\Entity\User;
use Spot\Auth\Repository\TokenRepository;
use Spot\Auth\Service\TokenService;

/** @mixin  TokenService */
class TokenServiceSpec extends ObjectBehavior
{
    /** @var  TokenRepository */
    private $tokenRepository;

    /** @var  int */
    private $tokenMaxAge;

    public function let(TokenRepository $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
        $this->tokenMaxAge = 42;
        $this->beConstructedWith($tokenRepository, $this->tokenMaxAge);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TokenService::class);
    }

    public function it_can_create_tokens_for_users(User $user)
    {
        $user->getUuid()->willReturn(Uuid::uuid4());
        $this->tokenRepository->create(new Argument\Token\TypeToken(Token::class))->shouldBeCalled();
        $this->createTokenForUser($user)->shouldHaveType(Token::class);
    }

    public function it_can_get_a_token(Token $token)
    {
        $uuid = Uuid::uuid4();
        $passCode = bin2hex(random_bytes(20));

        $this->tokenRepository->getByUuid($uuid)->willReturn($token);
        $token->getPassCode()->willReturn($passCode);
        $token->getExpires()->willReturn(new \DateTimeImmutable('+42 seconds'));

        $this->getToken($uuid, $passCode)->shouldReturn($token);
    }

    public function it_cant_get_an_expired_token(Token $token)
    {
        $uuid = Uuid::uuid4();
        $passCode = bin2hex(random_bytes(20));

        $this->tokenRepository->getByUuid($uuid)->willReturn($token);
        $token->getPassCode()->willReturn($passCode);
        $token->getExpires()->willReturn(new \DateTimeImmutable('-42 seconds'));

        $this->shouldThrow(\RuntimeException::class)->duringGetToken($uuid, $passCode);
    }

    public function it_errors_on_invalid_passCode(Token $token)
    {
        $uuid = Uuid::uuid4();
        $passCode = bin2hex(random_bytes(20));

        $this->tokenRepository->getByUuid($uuid)->willReturn($token);
        $token->getPassCode()->willReturn('nope');

        $this->shouldThrow(\RuntimeException::class)->duringGetToken($uuid, $passCode);
    }

    public function it_can_refresh_a_token(Token $token)
    {
        $token->getUserUuid()->willReturn($userUuid = Uuid::uuid4());
        $this->tokenRepository->create(new Argument\Token\TypeToken(Token::class));
        $this->tokenRepository->delete($token);
        $newToken = $this->refresh($token);
        $newToken->shouldHaveType(Token::class);
        $newToken->getUserUuid()->shouldReturn($userUuid);
    }

    public function it_can_remove_a_token(Token $token)
    {
        $this->tokenRepository->delete($token)->shouldBeCalled();
        $this->remove($token);
    }

    public function it_can_remove_expired_tokens()
    {
        $this->tokenRepository->deleteExpired()->shouldBeCalled();
        $this->invalidateExpiredTokens();
    }
}
