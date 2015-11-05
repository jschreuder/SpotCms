<?php declare(strict_types=1);

namespace Spot\Cms\Application\Request;

use Spot\Cms\Application\Request\Message\RequestInterface;
use Spot\Cms\Application\Request\Message\ServerErrorRequest;

class RequestException extends \RuntimeException
{
    /** @var  RequestInterface */
    private $errorRequest;

    public function __construct(RequestInterface $errorRequest = null, int $code = 0)
    {
        $this->errorRequest = $errorRequest ?: new ServerErrorRequest();
        parent::__construct($this->errorRequest->getName(), $code ?: 500);
    }

    public function getRequestObject() : RequestInterface
    {
        return $this->errorRequest;
    }
}
