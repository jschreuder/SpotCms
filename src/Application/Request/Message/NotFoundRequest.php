<?php declare(strict_types=1);

namespace Spot\Api\Application\Request\Message;

class NotFoundRequest implements RequestInterface
{
    /** {@inheritdoc} */
    public function getRequestName() : string
    {
        return 'error.notFound';
    }
}
