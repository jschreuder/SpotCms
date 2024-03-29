<?php declare(strict_types = 1);

namespace Spot\Auth\Command;

use Spot\Auth\Repository\UserRepository;
use Spot\Auth\Value\EmailAddress;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteUserCommand extends Command
{
    public function __construct(private UserRepository $userRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('auth:delete-user')
            ->setDescription('Deletes an existing user')
            ->addArgument('email-address', InputArgument::REQUIRED, 'User e-mail address is their username');
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $user = $this->userRepository->getByEmailAddress(EmailAddress::get($input->getArgument('email-address')));
        $this->userRepository->delete($user);
    }
}
