<?php declare(strict_types = 1);

namespace Spot\Application\Http;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use jschreuder\Middle\View\ViewInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spot\Application\View\JsonApiViewInterface;

class JsonRequestBodyParser implements ServerMiddlewareInterface
{
    const JSON_CONTENT_TYPES = [
        ViewInterface::CONTENT_TYPE_JSON,
        JsonApiViewInterface::CONTENT_TYPE_JSON_API,
    ];

    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        if ($this->isJsonParsableRequest($request)) {
            $request = $request->withParsedBody($this->parseBody($request));
        }
        return $delegate->next($request);
    }

    private function isJsonParsableRequest(ServerRequestInterface $request) : bool
    {
        if (in_array(strtoupper($request->getMethod()), ['GET', 'HEAD'])) {
            return false;
        }

        if (!in_array(strtolower($request->getHeaderLine('Content-Type')), self::JSON_CONTENT_TYPES)) {
            return false;
        }

        return true;
    }

    private function parseBody(ServerRequestInterface $request) : array
    {
        $parsedBody = json_decode($request->getBody()->getContents(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Could not decode JSON body: ' . json_last_error_msg());
        }

        return $parsedBody;
    }
}
