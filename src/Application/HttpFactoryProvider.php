<?php declare(strict_types = 1);

namespace Spot\Application;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

trait HttpFactoryProvider
{
    public function getHttpResponseFactory(): ResponseFactoryInterface
    {
        return new class implements ResponseFactoryInterface {
            public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
            {
                return new Response('php://memory', intval($code));
            }
        };
    }
}
