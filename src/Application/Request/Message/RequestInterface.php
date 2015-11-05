<?php declare(strict_types=1);

namespace Spot\Cms\Application\Request\Message;

interface RequestInterface
{
    public function getRequestName() : string;
}
