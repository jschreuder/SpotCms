<?php declare(strict_types=1);

namespace Spot\Api\Request;

use Psr\Http\Message\RequestInterface as HttpRequestInterface;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Request\Message\ServerErrorRequest;

class RequestException extends \RuntimeException
{
    /** @var  RequestInterface */
    private $request;

    public function __construct(string $reason, RequestInterface $request = null, HttpRequestInterface $httpRequest)
    {
        $this->request = $request ?: new ServerErrorRequest([], $httpRequest);
        parent::__construct($reason);
    }

    public function getRequestObject() : RequestInterface
    {
        return $this->request;
    }
}
