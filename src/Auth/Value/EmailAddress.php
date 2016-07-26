<?php declare(strict_types = 1);

namespace Spot\Auth\Value;

use Spot\DataModel\Value\ValueInterface;

class EmailAddress implements ValueInterface
{
    public static function get(string $value) : EmailAddress
    {
        return new self($value);
    }

    /** @var  string */
    private $value;

    private function __construct(string $value)
    {
        $this->validateEmailAddress($value);
        $this->value = $value;
    }

    public function toString() : string
    {
        return $this->value;
    }

    /**
     * Must be a valid email address
     *
     * @return  void
     * @throws  \InvalidArgumentException
     */
    private function validateEmailAddress(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid e-mail address given: ' . $value);
        }
    }
}
