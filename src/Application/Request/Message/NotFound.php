<?php

namespace Spot\Cms\Application\Request\Message;

class NotFound implements RequestInterface
{
    /** {@inheritdoc} */
    public function getName() : string
    {
        return 'error.notFound';
    }

    /** {@inheritdoc} */
    public function validate()
    {
    }
}
