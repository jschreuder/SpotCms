<?php declare(strict_types = 1);

namespace Spot\Auth;

use PDO;
use Psr\Log\LoggerInterface;
use Spot\Auth\Repository\TokenRepository;
use Spot\Auth\Repository\UserRepository;
use Spot\DataModel\Repository\ObjectRepository;

interface AuthServiceProviderInterface
{
    public function getDatabase(): PDO;

    public function getLogger(): LoggerInterface;

    public function getObjectRepository(): ObjectRepository;

    public function getTokenRepository(): TokenRepository;

    public function getUserRepository(): UserRepository;

    public function getTokenService(): TokenService;

    public function getAuthenticationService(): AuthenticationService;
}
