<?php declare(strict_types = 1);

namespace Spot\Application\View;

use jschreuder\Middle\View\RendererInterface;
use jschreuder\Middle\View\ViewInterface;
use Psr\Http\Factory\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Resource;
use Tobscure\JsonApi\SerializerInterface;

class JsonApiRenderer implements RendererInterface
{
    /** @var  ResponseFactoryInterface */
    private $responseFactory;

    /** @var  SerializerInterface */
    private $serializer;

    public function __construct(ResponseFactoryInterface $responseFactory, SerializerInterface $serializer)
    {
        $this->responseFactory = $responseFactory;
        $this->serializer = $serializer;
    }

    public function render(ServerRequestInterface $request, ViewInterface $view) : ResponseInterface
    {
        if (!$view instanceof JsonApiViewInterface) {
            throw new \InvalidArgumentException('JsonApiRenderer only support JsonApiViewInterface instances');
        }

        // Generate Response
        $response = $this->getResponse($view);

        // Generate body
        $body = $response->getBody();
        $body->rewind();
        $body->write(json_encode($this->getDocument($view)->toArray()));

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

    private function getDocument(JsonApiViewInterface $view) : Document
    {
        if ($view->isCollection()) {
            $element = (new Collection($view->getData(), $this->serializer))->with($view->getIncludes());
        } else {
            $element = (new Resource($view->getData(), $this->serializer))->with($view->getIncludes());
        }

        foreach ($view->getMetaData() as $key => $value) {
            $element->addMeta($key, $value);
        }

        return new Document($element);
    }
}
