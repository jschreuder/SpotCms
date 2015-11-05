<?php declare(strict_types=1);

namespace Spot\Cms\Application\Request\Message;

class NotFound implements RequestInterface
{
    /** {@inheritdoc} */
    public function getRequestName() : string
    {
        return 'error.notFound';
    }
}
