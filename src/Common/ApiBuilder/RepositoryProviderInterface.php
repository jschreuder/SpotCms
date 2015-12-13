<?php declare(strict_types=1);

namespace Spot\Common\ApiBuilder;

use Pimple\Container;

interface RepositoryProviderInterface
{
    public function provideRepositories(Container $container);
}
