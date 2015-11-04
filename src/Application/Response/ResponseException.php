<?php

namespace Spot\Cms\Application\Response;

use Spot\Cms\Application\Response\Message\ResponseInterface;
use Spot\Cms\Application\Response\Message\ServerError;

class ResponseException extends \RuntimeException
{
    /** @var  ResponseInterface */
    private $errorResponse;

    public function __construct(ResponseInterface $errorResponse = null, int $code = 0)
    {
        $this->errorResponse = $errorResponse ?: new ServerError();
        parent::__construct($this->errorResponse->getName(), $code ?: 500);
    }

    public function getResponseObject() : ResponseInterface
    {
        return $this->errorResponse;
    }
}
