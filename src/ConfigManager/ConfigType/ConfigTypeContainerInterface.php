<?php declare(strict_types = 1);

namespace Spot\ConfigManager\ConfigType;

interface ConfigTypeContainerInterface
{
    public function getType(string $typeName) : ConfigTypeInterface;
}
