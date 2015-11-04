<?php

namespace Spot\Cms\Application\Request\Message;

class ServerError implements RequestInterface
{
    /** {@inheritdoc} */
    public function getName()
    {
        return 'error.serverError';
    }

    /** {@inheritdoc} */
    public function validate()
    {
        return true;
    }
}
