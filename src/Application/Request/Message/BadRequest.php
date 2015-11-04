<?php declare(strict_types=1);

namespace Spot\Cms\Application\Request\Message;

class BadRequest implements RequestInterface
{
    /** {@inheritdoc} */
    public function getName() : string
    {
        return 'error.badRequest';
    }

    /** {@inheritdoc} */
    public function validate()
    {
    }
}
