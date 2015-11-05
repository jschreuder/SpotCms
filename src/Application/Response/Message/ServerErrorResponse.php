<?php declare(strict_types=1);

namespace Spot\Cms\Application\Response\Message;

class ServerErrorResponse implements ResponseInterface
{
    /** {@inheritdoc} */
    public function getResponseName() : string
    {
        return 'error.serverError';
    }
}
