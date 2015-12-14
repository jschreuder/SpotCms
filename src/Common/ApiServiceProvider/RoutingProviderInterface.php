<?php declare(strict_types = 1);

namespace Spot\Common\ApiServiceProvider;

use Pimple\Container;

interface RoutingProviderInterface
{
    const JSON_API_CT = 'application/vnd.api+json';

    public function registerRouting(Container $container, ApiServiceProvider $builder);
}
