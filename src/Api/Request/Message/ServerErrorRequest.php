<?php declare(strict_types = 1);

namespace Spot\Api\Request\Message;

use Psr\Http\Message\RequestInterface as HttpRequestInterface;
use Spot\Api\Message\AttributesArrayAccessTrait;

class ServerErrorRequest implements RequestInterface
{
    use AttributesArrayAccessTrait;

    /** @var  array */
    private $attributes;

    /** @var  string */
    private $acceptContentType;

    public function __construct(array $attributes, HttpRequestInterface $httpRequest)
    {
        $this->attributes = $attributes;
        $this->acceptContentType = $httpRequest->getHeaderLine('Accept');
    }

    /** {@inheritdoc} */
    public function getRequestName() : string
    {
        return 'error.serverError';
    }

    /** {@inheritdoc} */
    public function getAcceptContentType() : string
    {
        return $this->acceptContentType;
    }
}
