<?php declare(strict_types = 1);

namespace Spot\Application\View;

use jschreuder\Middle\View\RendererInterface;
use jschreuder\Middle\View\ViewInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonRenderer implements RendererInterface
{
    public function render(ServerRequestInterface $request, ViewInterface $view): ResponseInterface
    {
        if (!$view instanceof JsonViewInterface) {
            throw new \InvalidArgumentException('JsonRenderer only support JsonViewInterface instances');
        }

        // Generate Response
        $response = new JsonResponse($this->getDataFromView($view), $view->getStatusCode());
        foreach ($view->getHeaders() as $headerName => $headerContent) {
            $response = $response->withAddedHeader($headerName, $headerContent);
        }
        return $response;
    }

    protected function getDataFromView(JsonViewInterface $view): array
    {
        return [
            'meta' => $view->getMetaData(),
            'data' => $view->getData(),
        ];
    }
}
