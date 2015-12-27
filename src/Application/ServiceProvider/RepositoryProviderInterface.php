<?php declare(strict_types = 1);

namespace Spot\Application\ServiceProvider;

use Pimple\Container;

interface RepositoryProviderInterface
{
    public function registerRepositories(Container $container);
}
