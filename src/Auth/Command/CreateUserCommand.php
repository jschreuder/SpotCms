<?php declare(strict_types = 1);

namespace Spot\Auth\Command;

use Spot\Auth\AuthenticationService;
use Spot\Auth\Value\EmailAddress;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends Command
{
    /** @var  AuthenticationService */
    private $authenticationService;

    public function __construct(AuthenticationService $authenticationService)
    {
        parent::__construct();
        $this->authenticationService = $authenticationService;
    }

    protected function configure()
    {
        $this
            ->setName('auth:create-user')
            ->setDescription('Creates a new user')
            ->addArgument('email-address', InputArgument::REQUIRED, 'User e-mail address is their username')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
            ->addArgument('display-name', InputArgument::OPTIONAL, 'The name with which the user is displayed', '');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->authenticationService->createUser(
            EmailAddress::get($input->getArgument('email-address')),
            $input->getArgument('password'),
            $this->getDisplayName($input->getArgument('display-name'), $input->getArgument('email-address'))
        );
    }

    private function getDisplayName(string $displayName, string $emailAddress) : string
    {
        if ($displayName) {
            return $displayName;
        }

        $parts = explode('@', $emailAddress);
        return $parts[0];
    }
}
