<?php declare(strict_types=1);

namespace Spot\Api\Application\Response;

use Spot\Api\Application\Response\Message\ResponseInterface;
use Spot\Api\Application\Response\Message\ServerErrorResponse;

class ResponseException extends \RuntimeException
{
    /** @var  ResponseInterface */
    private $errorResponse;

    public function __construct(ResponseInterface $errorResponse = null, int $code = 0)
    {
        $this->errorResponse = $errorResponse ?: new ServerErrorResponse();
        parent::__construct($this->errorResponse->getResponseName(), $code ?: 500);
    }

    public function getResponseObject() : ResponseInterface
    {
        return $this->errorResponse;
    }
}
