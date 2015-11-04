<?php declare(strict_types=1);

namespace Spot\Cms\Application\Request\Message;

class ServerError implements RequestInterface
{
    /** {@inheritdoc} */
    public function getName() : string
    {
        return 'error.serverError';
    }

    /** {@inheritdoc} */
    public function validate()
    {
    }
}
