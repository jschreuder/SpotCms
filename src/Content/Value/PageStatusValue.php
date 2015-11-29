<?php declare(strict_types=1);

namespace Spot\Api\Content\Value;

use Spot\Api\Common\Value\ValueInterface;

class PageStatusValue implements ValueInterface
{
    const CONCEPT = 'concept';
    const PUBLISHED = 'published';
    const DELETED = 'deleted';

    public static function get(string $value) : ValueInterface
    {
        return new self($value);
    }

    public static function getValidStatuses() : array
    {
        return [self::CONCEPT, self::PUBLISHED, self::DELETED];
    }

    /** @var  string */
    private $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::getValidStatuses(), true)) {
            throw new \InvalidArgumentException('Invalid PageStatus given: ' . $value);
        }
        $this->value = $value;
    }

    public function toString() : string
    {
        return $this->value;
    }
}
