<?php declare(strict_types=1);

namespace Spot\Cms\Content\Value;

use Spot\Cms\Common\Entity\ValueInterface;

class PageStatusValue implements ValueInterface
{
    const CONCEPT = 'concept';
    const PUBLISHED = 'published';

    public static function get(string $value) : self
    {
        return new self($value);
    }

    /** @var  string */
    private $value;

    public function __construct(string $value)
    {
        if (!in_array($value, [self::CONCEPT, self::PUBLISHED], true)) {
            throw new \InvalidArgumentException('Invalid PageStatus given: ' . $value);
        }
        $this->value = $value;
    }

    public function toString()
    {
        return $this->value;
    }
}
