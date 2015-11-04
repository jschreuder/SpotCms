<?php

namespace Spot\Cms\Application\Response\Message;

class ServerError implements ResponseInterface
{
    /** {@inheritdoc} */
    public function getName()
    {
        return 'error.serverError';
    }
}
