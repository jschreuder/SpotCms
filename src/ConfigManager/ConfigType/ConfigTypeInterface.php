<?php declare(strict_types = 1);

namespace Spot\ConfigManager\ConfigType;

interface ConfigTypeInterface
{
    public function getTypeName() : string;

    public function validate();

    public function getDefaultItems() : array;
}