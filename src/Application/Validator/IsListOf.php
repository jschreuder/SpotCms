<?php declare(strict_types=1);

namespace Spot\Application\Validator;

use Laminas\Validator\AbstractValidator;

final class IsListOf extends AbstractValidator
{
    /** @var  callable */
    private $checker;

    public function __construct(callable $checker)
    {
        $this->checker = $checker;
        parent::__construct();
    }

    public function isValid($value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!call_user_func($this->checker, $item)) {
                return false;
            }
        }
        
        return true;
    }
}
