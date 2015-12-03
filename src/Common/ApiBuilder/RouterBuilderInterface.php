<?php declare(strict_types=1);

namespace Spot\Common\ApiBuilder;

use Pimple\Container;

interface RouterBuilderInterface
{
    public function configureRouting(Container $container, ApiBuilder $builder);
}
