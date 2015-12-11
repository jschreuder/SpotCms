<?php declare(strict_types=1);

namespace Spot\Api\Response;

use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;

class ResponseException extends \RuntimeException
{
    /** @var  ResponseInterface */
    private $errorResponse;

    public function __construct(string $reason, ResponseInterface $errorResponse)
    {
        $this->errorResponse = $errorResponse;
        parent::__construct($reason);
    }

    public function getResponseObject() : ResponseInterface
    {
        return $this->errorResponse;
    }
}
