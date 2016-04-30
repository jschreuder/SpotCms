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
        $this->validatePath($path);
        if ($path !== '/') {
            $segments = explode('/', trim($path, '/'));
            foreach ($segments as $segment) {
                $this->validateSegments($segment);
            }
        }
        $this->value = $path;
    }

    /**
     * Path must not contain illegal characters, not be empty or start/end with something
     * other than a slash.
     *
     * @return  void
     * @throws  \InvalidArgumentException
     */
    private function validatePath(string $path)
    {
        if (
            preg_match('#(\.\.|[\0\n\r\t<>])#', $path) !== 0
            || !in_array($path, [('/' . trim($path, '/')), '/'])
        ) {
            throw new \InvalidArgumentException('Invalid path given: "' . $path . '"');
        }
    }

    /**
     * Directories in path must not have leading or trailing spaces and not be empty
     *
     * @return  void
     * @throws  \InvalidArgumentException
     */
    private function validateSegments(string $segment)
    {
        if (trim($segment) !== $segment || strlen($segment) === 0) {
            throw new \InvalidArgumentException('Invalid segment in given path: "' . $segment . '"');
        }
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
