<?php declare(strict_types=1);

namespace Spot\Common\ApiBuilder;

use Pimple\Container;

interface RoutingProviderInterface
{
    const JSON_API_CT = 'application/vnd.api+json';

    public function provideRouting(Container $container, ApiBuilder $builder);
}
