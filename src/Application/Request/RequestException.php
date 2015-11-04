<?php

namespace Spot\Cms\Application\Request;

use Spot\Cms\Application\Request\Message\RequestInterface;
use Spot\Cms\Application\Request\Message\ServerError;

class RequestException extends \RuntimeException
{
    /** @var  RequestInterface */
    private $errorRequest;

    /**
     * @param  RequestInterface|null $errorRequest
     * @param  int $code
     */
    public function __construct(RequestInterface $errorRequest = null, $code = 0)
    {
        $this->errorRequest = $errorRequest ?: new ServerError();
        parent::__construct($this->errorRequest->getName(), $code ?: 500);
    }

    /** @return  RequestInterface */
    public function getRequestObject()
    {
        return $this->errorRequest;
    }
}
