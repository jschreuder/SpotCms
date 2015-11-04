<?php declare(strict_types=1);

namespace Spot\Cms\Application\Response\Message;

class ServerError implements ResponseInterface
{
    /** {@inheritdoc} */
    public function getName() : string
    {
        return 'error.serverError';
    }
}
