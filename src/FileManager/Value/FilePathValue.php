<?php declare(strict_types = 1);

namespace Spot\FileManager\Value;

use Spot\DataModel\Value\ValueInterface;

class FilePathValue implements ValueInterface
{
    /** @var  string */
    private $value;

    public static function get(string $path) : FilePathValue
    {
        return new self($path);
    }

    private function __construct(string $path)
    {
        if (
            preg_match('#(\.\.|[\0\n\r\t<>])#', $path) !== 0 // must not contain disallowed characters
            || strlen($path) === 0 // must not be empty
        ) {
            throw new \InvalidArgumentException('Invalid path given: ' . $path);
        }

        // directories in path must not have leading or trailing spaces and not be empty
        $segments = explode('/', trim($path, '/'));
        foreach ($segments as $segment) {
            if (trim($segment) !== $segment || strlen($segment) === 0) {
                throw new \InvalidArgumentException('Invalid path given: ' . $path);
            }
        }

        $this->value = $path;
    }

    public function toString() : string
    {
        return $this->value;
    }

    public function getDirectoryName() : string
    {
        return basename($this->value);
    }
}
