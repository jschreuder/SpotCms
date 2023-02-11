<?php declare(strict_types = 1);

namespace Spot\Application\View;

use jschreuder\Middle\View\View;
use jschreuder\Middle\View\ViewInterface;

class JsonApiView implements JsonApiViewInterface
{
    private ViewInterface $view;
    private bool $isCollection;
    /** @var  string[] */
    private array $includes;

    public function __construct(
        mixed $data,
        bool $isCollection = false,
        array $includes = [],
        array $metaData = [],
        int $statusCode = 200,
        array $headers = []
    )
    {
        $parameters = [
            'data' => $data,
            'meta' => $metaData,
        ];
        $this->view = new View('json-api', $parameters, $statusCode, self::CONTENT_TYPE_JSON_API, $headers);
        $this->isCollection = $isCollection;
        $this->includes = $includes;
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
        throw new \BadMethodCallException('JsonApiViews do not support templates');
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

    public function getIncludes(): array
    {
        return $this->includes;
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
