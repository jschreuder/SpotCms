<?php declare(strict_types=1);

namespace Spot\Common\ApiServiceProvider;

use Pimple\Container;

interface RepositoryProviderInterface
{
    public function registerRepositories(Container $container);
}
