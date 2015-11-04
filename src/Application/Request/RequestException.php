<?php

namespace Spot\Cms\Application\Request;

use Spot\Cms\Application\Request\Message\RequestInterface;
use Spot\Cms\Application\Request\Message\ServerError;

class RequestException extends \RuntimeException
{
    /** @var  RequestInterface */
    private $errorRequest;

    public function __construct(RequestInterface $errorRequest = null, int $code = 0)
    {
        $this->errorRequest = $errorRequest ?: new ServerError();
        parent::__construct($this->errorRequest->getName(), $code ?: 500);
    }

    public function getRequestObject() : RequestInterface
    {
        return $this->errorRequest;
    }
}
