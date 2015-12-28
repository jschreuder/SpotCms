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
            || $path[0] !== '/' // must start with a slash
            || $path[strlen($path) - 1] !== '/' // must end with a slash
        ) {
            throw new \InvalidArgumentException('Invalid path given: "' . $path . '"');
        }

        // directories in path must not have leading or trailing spaces and not be empty
        $segments = explode('/', trim($path, '/'));
        if ($segments !== ['']) {
            foreach ($segments as $segment) {
                if (trim($segment) !== $segment || strlen($segment) === 0) {
                    throw new \InvalidArgumentException('Invalid segment in given path: "' . $segment . '"');
                }
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
