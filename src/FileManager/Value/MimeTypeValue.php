<?php declare(strict_types = 1);

namespace Spot\FileManager\Value;

use Spot\DataModel\Value\ValueInterface;

class MimeTypeValue implements ValueInterface
{
    /** @var  string */
    private $value;

    public static function get(string $mimeType) : MimeTypeValue
    {
        return new self($mimeType);
    }

    private function __construct(string $mimeType)
    {
        if (preg_match('#^[a-z0-9_\-\.\+]+/[a-z0-9_\-\.\+]+$#iD', $mimeType) === 0) {
            throw new \InvalidArgumentException('Invalid mime-type given: ' . $mimeType);
        }
        $this->value = $mimeType;
    }

    public function toString() : string
    {
        return $this->value;
    }
}
