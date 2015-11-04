<?php

namespace Spot\Cms\Application\Request;

class RequestError implements RequestInterface
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
