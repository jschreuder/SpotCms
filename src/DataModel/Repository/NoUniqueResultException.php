<?php declare(strict_types=1);

namespace Spot\DataModel\Repository;

use Exception;

class NoUniqueResultException extends \OverflowException
{
    public function __construct(
        string $message = 'No unique response when expecting single Entity',
        int $code,
        Exception $previous
    ) {
        parent::__construct($message, $code, $previous);
    }
}
