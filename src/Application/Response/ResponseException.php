<?php

namespace Spot\Cms\Application\Response;

use Spot\Cms\Application\Response\Message\ResponseInterface;
use Spot\Cms\Application\Response\Message\ServerError;

class ResponseException extends \RuntimeException
{
    /** @var  ResponseInterface */
    private $errorResponse;

    /**
     * @param  ResponseInterface|null $errorResponse
     * @param  int $code
     */
    public function __construct(ResponseInterface $errorResponse = null, $code = 0)
    {
        $this->errorResponse = $errorResponse ?: new ServerError();
        parent::__construct($this->errorResponse->getName(), $code ?: 500);
    }

    /** @return  ResponseInterface */
    public function getResponseObject()
    {
        return $this->errorResponse;
    }
}
