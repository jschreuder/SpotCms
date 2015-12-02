<?php declare(strict_types=1);

namespace Spot\Api\Request;

use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Request\Message\ServerErrorRequest;

class RequestException extends \RuntimeException
{
    /** @var  RequestInterface */
    private $errorRequest;

    public function __construct(RequestInterface $errorRequest = null, int $code = 0)
    {
        $this->errorRequest = $errorRequest ?: new ServerErrorRequest();
        parent::__construct($this->errorRequest->getRequestName(), $code ?: 500);
    }

    public function getRequestObject() : RequestInterface
    {
        return $this->errorRequest;
    }
}
