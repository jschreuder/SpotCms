<?php declare(strict_types=1);

namespace Spot\Api\Response\Message;

interface ResponseInterface
{
    public function getResponseName() : string;
}
