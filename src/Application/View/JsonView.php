<?php declare(strict_types = 1);

namespace Spot\Application\View;

use jschreuder\Middle\View\View;
use jschreuder\Middle\View\ViewInterface;

class JsonView implements JsonViewInterface
{
    private ViewInterface $view;

    public function __construct(
        mixed $data,
        private bool $isCollection = false,
        array $metaData = [],
        int $statusCode = 200,
        array $headers = []
    )
    {
        $parameters = [
            'data' => $data,
            'meta' => $metaData,
        ];
        $this->view = new View('json', $parameters, $statusCode, 'application/json', $headers);
    }

    public function getStatusCode(): int
    {
        return $this->view->getStatusCode();
    }

    public function getContentType(): string
    {
        return $this->view->getContentType();
    }

    public function getHeaders(): array
    {
        return $this->view->getHeaders();
    }

    public function setHeader(string $key, string $value): void
    {
        $this->view->setHeader($key, $value);
    }

    public function getTemplate(): string
    {
        throw new \BadMethodCallException('JsonViews do not use templates');
    }

    public function getParameters(): array
    {
        return $this->view->getParameters();
    }

    public function setParameter(string $key, $value): void
    {
        $this->view->setParameter($key, $value);
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    public function getData(): mixed
    {
        return $this->getParameters()['data'];
    }

    public function setMetaData(string $key, $value): void
    {
        $meta = $this->view->getParameters()['meta'];
        $meta[$key] = $value;
        $this->view->setParameter('meta', $meta);
    }

    public function getMetaData(): array
    {
        return $this->view->getParameters()['meta'];
    }
}
