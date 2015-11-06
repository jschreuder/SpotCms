<?php declare(strict_types=1);

namespace Spot\Api\Content\Value;

use Spot\Api\Common\Entity\ValueInterface;

class PageStatusValue implements ValueInterface
{
    const CONCEPT = 'concept';
    const PUBLISHED = 'published';

    public static function get(string $value) : self
    {
        return new self($value);
    }

    public static function getValidStatuses() : array
    {
        return [self::CONCEPT, self::PUBLISHED];
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

    public function toString()
    {
        return $this->value;
    }
}
