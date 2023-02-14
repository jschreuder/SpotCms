<?php declare(strict_types = 1);

namespace Spot\FileManager\Value;

use Spot\DataModel\Value\ValueInterface;

class FileNameValue implements ValueInterface
{
    public static function get(string $value): self
    {
        return new self($value);
    }

    private function __construct(private string $value)
    {
        $this->validateFileName($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * FileName must not contain any illegal characters, be empty or have leading or
     * trailing spaces.
     *
     * @throws  \InvalidArgumentException
     */
    private function validateFileName(string $value): void
    {
        if (
            preg_match('#(\.\.|[/\0\n\r\t<>])#', $value) !== 0
            || strlen($value) === 0
            || $value !== trim($value)
        ) {
            throw new \InvalidArgumentException('Invalid FileName given: "' . $value . '"');
        }
    }
}
