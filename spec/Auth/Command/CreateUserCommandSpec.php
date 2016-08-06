<?php

namespace spec\Spot\Auth\Command;

use PhpSpec\ObjectBehavior;
use Spot\Auth\Command\CreateUserCommand;
use Spot\Auth\AuthenticationService;
use Spot\Auth\Value\EmailAddress;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @mixin  CreateUserCommand */
class CreateUserCommandSpec extends ObjectBehavior
{
    /** @var  AuthenticationService */
    private $authenticationService;

    public function let(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
        $this->beConstructedWith($authenticationService);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CreateUserCommand::class);
    }

    public function it_can_be_executed(InputInterface $input, OutputInterface $output)
    {
        $email = 'joe@biden.de';
        $pass = 'malarky';
        $display = 'JOE!';
        $input->getArgument('email-address')->willReturn($email);
        $input->getArgument('password')->willReturn($pass);
        $input->getArgument('display-name')->willReturn($display);

        $this->authenticationService->createUser(EmailAddress::get($email), $pass, $display);

        $this->execute($input, $output);
    }

    public function it_can_be_executed_and_derive_display_name(InputInterface $input, OutputInterface $output)
    {
        $email = 'joe@biden.de';
        $pass = 'malarky';
        $input->getArgument('email-address')->willReturn($email);
        $input->getArgument('password')->willReturn($pass);
        $input->getArgument('display-name')->willReturn('');

        $this->authenticationService->createUser(EmailAddress::get($email), $pass, 'joe');

        $this->execute($input, $output);
    }
}
