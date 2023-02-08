<?php declare(strict_types = 1);

namespace Spot\Auth;

use PDO;
use Psr\Log\LoggerInterface;
use Spot\Auth\Repository\TokenRepository;
use Spot\Auth\Repository\UserRepository;
use Spot\DataModel\Repository\ObjectRepository;

trait AuthServiceProvider
{
    abstract public function getDatabase(): PDO;
    abstract public function getLogger(): LoggerInterface;
    abstract public function getObjectRepository(): ObjectRepository;

    public function getTokenRepository(): TokenRepository
    {
        return new TokenRepository($this->getDatabase());
    }

    public function getUserRepository(): UserRepository
    {
        return new UserRepository($this->getDatabase(), $this->getObjectRepository());
    }

    public function getTokenService(): TokenService
    {
        return new TokenService($this->getTokenRepository(), 3600 * 24 * 1);
    }

    public function getAuthenticationService(): AuthenticationService
    {
        return new AuthenticationService(
            $this->getUserRepository(),
            $this->getTokenService(),
            $this->getLogger()
        );
    }
}
