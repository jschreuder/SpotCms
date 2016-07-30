<?php

namespace spec\Spot\Auth\Command;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\Auth\Command\DeleteUserCommand;
use Spot\Auth\Entity\User;
use Spot\Auth\Repository\UserRepository;
use Spot\Auth\Value\EmailAddress;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @mixin  DeleteUserCommand */
class DeleteUserCommandSpec extends ObjectBehavior
{
    /** @var  UserRepository */
    private $userRepository;

    public function let(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->beConstructedWith($userRepository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(DeleteUserCommand::class);
    }

    public function it_can_be_executed(InputInterface $input, OutputInterface $output, User $user)
    {
        $email = 'the@donald.gold';
        $input->getArgument('email-address')->willReturn($email);

        $this->userRepository->getByEmailAddress(EmailAddress::get($email))->willReturn($user);
        $this->userRepository->delete($user)->shouldBeCalled();

        $this->execute($input, $output);
    }
}
