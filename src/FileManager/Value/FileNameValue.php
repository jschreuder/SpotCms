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
        $this->validateFileName($fileName);
        $this->value = $fileName;
    }

    /**
     * FileName must not contain any illegal characters, be empty or have leading or
     * trailing spaces.
     *
     * @return  void
     * @throws  \InvalidArgumentException
     */
    private function validateFileName(string $fileName)
    {
        if (
            preg_match('#(\.\.|[/\0\n\r\t<>])#', $fileName) !== 0
            || strlen($fileName) === 0
            || $fileName !== trim($fileName)
        ) {
            throw new \InvalidArgumentException('Invalid FileName given: "' . $fileName . '"');
        }
    }

    public function toString() : string
    {
        return $this->value;
    }
}
