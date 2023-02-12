<?php declare(strict_types = 1);

namespace Spot\FileManager\Value;

use Spot\DataModel\Value\ValueInterface;

class MimeTypeValue implements ValueInterface
{
    public static function get(string $value): MimeTypeValue
    {
        return new self($value);
    }

    private function __construct(private string $value)
    {
        $this->validateMimeType($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * A mime-type must match the expected regex pattern
     *
     * @throws  \InvalidArgumentException
     */
    private function validateMimeType(string $value): void
    {
        if (preg_match('#^[a-z0-9_\-\.\+]+/[a-z0-9_\-\.\+]+$#iD', $value) === 0) {
            throw new \InvalidArgumentException('Invalid mime-type given: ' . $value);
        }
    }
}
