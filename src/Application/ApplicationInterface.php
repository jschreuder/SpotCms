<?php

namespace Spot\Cms\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as HttpRequest;

interface ApplicationInterface
{
    public function execute(HttpRequest $httpRequest) : ResponseInterface;
}
