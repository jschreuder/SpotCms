<?php

namespace spec\Spot\Auth;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Auth\Entity\Token;
use Spot\Auth\Entity\User;
use Spot\Auth\Exception\LoginFailedException;
use Spot\Auth\Repository\UserRepository;
use Spot\Auth\AuthenticationService;
use Spot\Auth\TokenService;
use Spot\Auth\Value\EmailAddress;
use Spot\DataModel\Repository\NoUniqueResultException;

/** @mixin  AuthenticationService */
class AuthenticationServiceSpec extends ObjectBehavior
{
    /** @var  UserRepository */
    private $userRepository;

    /** @var  TokenService */
    private $tokenService;

    /** @var  LoggerInterface */
    private $logger;

    public function let(UserRepository $userRepository, TokenService $tokenService, LoggerInterface $logger)
    {
        $this->userRepository = $userRepository;
        $this->tokenService = $tokenService;
        $this->logger = $logger;
        $this->beConstructedWith($userRepository, $tokenService, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AuthenticationService::class);
    }

    public function it_can_create_a_user()
    {
        $email = 'r2@d2.bot';
        $password = 'please.stop.talking.c3po';
        $displayName = 'R2D2';

        $this->userRepository->create(new Argument\Token\TypeToken(User::class))->shouldBeCalled();

        $user = $this->createUser(EmailAddress::get($email), $password, $displayName);
        $user->getUuid()->shouldHaveType(UuidInterface::class);
        $user->getEmailAddress()->toString()->shouldReturn($email);
        $user->getDisplayName()->shouldReturn($displayName);

        $passwordHash = $user->getPassword()->getWrappedObject();
        if (!password_verify($password, $passwordHash)) {
            throw new \RuntimeException('Password did not verify.');
        }
    }

    public function it_can_login_a_user(User $user, Token $token)
    {
        $email = 'r2@d2.bot';
        $password = 'please.stop.talking.c3po';

        $this->userRepository->getByEmailAddress(EmailAddress::get($email))->willReturn($user);
        $user->getPassword()->willReturn(password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]));

        $this->tokenService->createTokenForUser($user)->willReturn($token);

        $this->login($email, $password)->shouldReturn($token);
    }

    public function it_can_login_a_user_and_rehash_password(User $user, Token $token)
    {
        $email = 'r2@d2.bot';
        $password = 'please.stop.talking.c3po';

        $this->userRepository->getByEmailAddress(EmailAddress::get($email))->willReturn($user);
        $user->getPassword()->willReturn(password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]));

        $user->setPassword(new Argument\Token\StringContainsToken('$2y$10$'))->willReturn($user);
        $this->userRepository->update($user)->shouldBeCalled();

        $this->tokenService->createTokenForUser($user)->willReturn($token);

        $this->login($email, $password)->shouldReturn($token);
    }

    public function it_errors_on_invalid_email_address()
    {
        $email = 'r2-d2.bot';
        $password = 'please.stop.talking.c3po';
        $this->shouldThrow(LoginFailedException::invalidEmailAddress())->duringLogin($email, $password);
    }

    public function it_errors_on_invalid_username()
    {
        $email = 'r2@d2.bot';
        $password = 'please.stop.talking.c3po';

        $this->userRepository->getByEmailAddress(EmailAddress::get($email))->willThrow(new NoUniqueResultException());
        $this->shouldThrow(LoginFailedException::invalidCredentials())->duringLogin($email, $password);
    }

    public function it_errors_on_invalid_password(User $user)
    {
        $email = 'r2@d2.bot';
        $password = 'please.stop.talking.c3po';

        $this->userRepository->getByEmailAddress(EmailAddress::get($email))->willReturn($user);
        $user->getPassword()->willReturn(password_hash('no.you.shut.up.r2', PASSWORD_BCRYPT, ['cost' => 10]));
        $this->shouldThrow(LoginFailedException::invalidCredentials())->duringLogin($email, $password);
    }

    public function it_errors_on_general_error()
    {
        $email = 'r2@d2.bot';
        $password = 'please.stop.talking.c3po';

        $this->userRepository->getByEmailAddress(EmailAddress::get($email))->willThrow(new \RuntimeException());
        $this->shouldThrow(LoginFailedException::systemError())->duringLogin($email, $password);
    }

    public function it_can_get_a_user_by_token_and_pass_code(Token $token, User $user)
    {
        $tokenUuid = Uuid::uuid4();
        $passCode = bin2hex(random_bytes(20));
        $userUuid = Uuid::uuid4();

        $this->tokenService->getToken($tokenUuid, $passCode)->willReturn($token);
        $token->getUserUuid()->willReturn($userUuid);

        $this->userRepository->getByUuid($userUuid)->willReturn($user);

        $this->getUserForToken($tokenUuid, $passCode);
    }

    public function it_errors_on_invalid_token()
    {
        $tokenUuid = Uuid::uuid4();
        $passCode = bin2hex(random_bytes(20));

        $this->tokenService->getToken($tokenUuid, $passCode)->willThrow(new NoUniqueResultException());
        $this->shouldThrow(LoginFailedException::invalidToken())->duringGetUserForToken($tokenUuid, $passCode);
    }

    public function it_handles_other_exceptions()
    {
        $tokenUuid = Uuid::uuid4();
        $passCode = bin2hex(random_bytes(20));

        $this->tokenService->getToken($tokenUuid, $passCode)->willThrow(new \Exception());
        $this->shouldThrow(LoginFailedException::systemError())->duringGetUserForToken($tokenUuid, $passCode);
    }

    public function it_can_logout(Token $token)
    {
        $this->tokenService->remove($token)->shouldBeCalled();
        $this->logout($token);
    }
}
