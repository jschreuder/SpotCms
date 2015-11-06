<?php declare(strict_types=1);

namespace Spot\Api\Common\Entity;

interface ValueInterface
{
    public static function get(string $value) : self;

    public function toString() : string;
}
