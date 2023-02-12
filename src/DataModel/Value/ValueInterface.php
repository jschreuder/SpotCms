<?php declare(strict_types = 1);

namespace Spot\DataModel\Value;

interface ValueInterface
{
    public static function get(string $value): mixed;

    public function toString(): string;
}
