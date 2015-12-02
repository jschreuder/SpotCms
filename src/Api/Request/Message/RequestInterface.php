<?php declare(strict_types=1);

namespace Spot\Api\Request\Message;

interface RequestInterface
{
    public function getRequestName() : string;
}
