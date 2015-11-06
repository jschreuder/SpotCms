<?php declare(strict_types=1);

namespace Spot\Api\Application\Request\Message;

class ServerErrorRequest implements RequestInterface
{
    /** {@inheritdoc} */
    public function getRequestName() : string
    {
        return 'error.serverError';
    }
}
