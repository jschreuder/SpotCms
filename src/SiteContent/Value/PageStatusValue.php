<?php declare(strict_types = 1);

namespace Spot\SiteContent\Value;

use Spot\DataModel\Value\ValueInterface;

class PageStatusValue implements ValueInterface
{
    const CONCEPT = 'concept';
    const PUBLISHED = 'published';
    const DELETED = 'deleted';

    /**
     * @param   string $value
     * @return  self
     */
    public static function get(string $value) : PageStatusValue
    {
        return new self($value);
    }

    public static function getValidStatuses() : array
    {
        return [self::CONCEPT, self::PUBLISHED, self::DELETED];
    }

    /** @var  string */
    private $value;

    private function __construct(string $value)
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
