<?php declare(strict_types=1);

namespace Spot\Common\ApiBuilder;

use Pimple\Container;

interface RepositoryBuilderInterface
{
    public function configureRepositories(Container $container);
}
