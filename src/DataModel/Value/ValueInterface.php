<?php declare(strict_types = 1);

namespace Spot\DataModel\Value;

interface ValueInterface
{
    /** @return  ValueInterface */
    public static function get(string $value);

    public function toString() : string;
}
