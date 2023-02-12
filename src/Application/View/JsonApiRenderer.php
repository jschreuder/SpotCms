<?php declare(strict_types = 1);

namespace Spot\Application\View;

use jschreuder\Middle\View\RendererInterface;
use jschreuder\Middle\View\ViewInterface;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonApiRenderer implements RendererInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private EncoderInterface $encoder
    )
    {
    }

    public function render(ServerRequestInterface $request, ViewInterface $view) : ResponseInterface
    {
        if (!$view instanceof JsonApiViewInterface) {
            throw new \InvalidArgumentException('JsonApiRenderer only support JsonApiViewInterface instances');
        }

        // Generate Response
        $response = $this->getResponse($view);

        // Generate body
        $json = $this->encoder->withIncludedPaths($view->getIncludes())
            ->withMeta($view->getMetaData())
            ->encodeData($view->getData());
        $body = $response->getBody();
        $body->rewind();
        $body->write($json);

        return $response;
    }

    private function getResponse(ViewInterface $view) : ResponseInterface
    {
        $response = $this->responseFactory->createResponse($view->getStatusCode())
            ->withHeader('Content-Type', $view->getContentType());
        foreach ($view->getHeaders() as $header => $value) {
            $response = $response->withHeader($header, $value);
        }
        return $response;
    }
}
