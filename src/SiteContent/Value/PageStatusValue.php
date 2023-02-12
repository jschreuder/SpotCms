<?php declare(strict_types = 1);

namespace Spot\SiteContent\Value;

use Spot\DataModel\Value\ValueInterface;

class PageStatusValue implements ValueInterface
{
    const CONCEPT = 'concept';
    const PUBLISHED = 'published';
    const DELETED = 'deleted';

    public static function get(string $value): self
    {
        return new self($value);
    }

    public static function getValidStatuses(): array
    {
        return [self::CONCEPT, self::PUBLISHED, self::DELETED];
    }

    private function __construct(private string $value)
    {
        $this->validateStatus($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Status must be one of the expected values
     *
     * @throws  \InvalidArgumentException
     */
    private function validateStatus(string $value): void
    {
        if (!in_array($value, self::getValidStatuses(), true)) {
            throw new \InvalidArgumentException('Invalid PageStatus given: ' . $value);
        }
    }
}
