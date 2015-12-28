<?php declare(strict_types = 1);

namespace Spot\FileManager\Value;

use Spot\DataModel\Value\ValueInterface;

class FileNameValue implements ValueInterface
{
    /** @var  string */
    private $value;

    public static function get(string $fileName) : FileNameValue
    {
        return new self($fileName);
    }

    private function __construct(string $fileName)
    {
        if (
            preg_match('#(\.\.|[/\0\n\r\t<>])#', $fileName) !== 0 // must not contain disallowed characters
            || strlen($fileName) === 0 // must not be empty
            || $fileName !== trim($fileName) // no leading or trailing spaces
        ) {
            throw new \InvalidArgumentException('Invalid FileName given: "' . $fileName.'"');
        }
        $this->value = $fileName;
    }

    public function toString() : string
    {
        return $this->value;
    }
}
