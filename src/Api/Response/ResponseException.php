<?php declare(strict_types=1);

namespace Spot\Api\Response;

use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;

class ResponseException extends \RuntimeException
{
    /** @var  ResponseInterface */
    private $errorResponse;

    public function __construct(string $reason, ResponseInterface $errorResponse = null, int $code = 0)
    {
        $this->reason = $reason;
        $this->errorResponse = $errorResponse ?: new ServerErrorResponse();
        parent::__construct($reason, $code);
    }

    public function getResponseObject() : ResponseInterface
    {
        return $this->errorResponse;
    }
}
