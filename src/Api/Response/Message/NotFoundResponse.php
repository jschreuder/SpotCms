<?php declare(strict_types=1);

namespace Spot\Api\Response\Message;

class NotFoundResponse implements ResponseInterface
{
    /** {@inheritdoc} */
    public function getResponseName() : string
    {
        return 'error.notFound';
    }
}
