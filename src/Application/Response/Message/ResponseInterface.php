<?php declare(strict_types=1);

namespace Spot\Api\Application\Response\Message;

interface ResponseInterface
{
    public function getResponseName() : string;
}
