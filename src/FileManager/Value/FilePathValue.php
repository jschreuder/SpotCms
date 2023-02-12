<?php declare(strict_types = 1);

namespace Spot\FileManager\Value;

use Spot\DataModel\Value\ValueInterface;

class FilePathValue implements ValueInterface
{
    public static function get(string $value): FilePathValue
    {
        return new self($value);
    }

    private function __construct(private string $value)
    {
        $this->validatePath($value);
        if ($value !== '/') {
            $segments = explode('/', trim($value, '/'));
            foreach ($segments as $segment) {
                $this->validateSegments($segment);
            }
        }
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function getDirectoryName(): string
    {
        return basename($this->value);
    }

    /**
     * Path must not contain illegal characters, not be empty or start/end with something
     * other than a slash.
     *
     * @throws  \InvalidArgumentException
     */
    private function validatePath(string $value): void
    {
        if (
            preg_match('#(\.\.|[\0\n\r\t<>])#', $value) !== 0
            || !in_array($value, [('/' . trim($value, '/')), '/'])
        ) {
            throw new \InvalidArgumentException('Invalid path given: "' . $value . '"');
        }
    }

    /**
     * Directories in path must not have leading or trailing spaces and not be empty
     *
     * @throws  \InvalidArgumentException
     */
    private function validateSegments(string $segment): void
    {
        if (trim($segment) !== $segment || strlen($segment) === 0) {
            throw new \InvalidArgumentException('Invalid segment in given path: "' . $segment . '"');
        }
    }
}
