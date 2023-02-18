<?php declare(strict_types = 1);

namespace Spot\SiteContent;

use jschreuder\Middle\View\RendererInterface;
use PDO;
use Spot\Application\View\JsonConverterRenderer;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\SiteContent\JsonConverter\PageBlockJsonConverter;
use Spot\SiteContent\JsonConverter\PageJsonConverter;
use Spot\SiteContent\Repository\PageRepository;

trait SiteContentServiceProvider
{
    abstract public function config(string $valueName): mixed;
    abstract public function getDatabase(): PDO;
    abstract public function getObjectRepository(): ObjectRepository;

    public function getPageRepository(): PageRepository
    {
        return new PageRepository(
            $this->getDatabase(),
            $this->getObjectRepository()
        );
    }

    public function getPageRenderer(): RendererInterface
    {
        return new JsonConverterRenderer(new PageJsonConverter());
    }

    public function getPageBlockRenderer(): RendererInterface
    {
        return new JsonConverterRenderer(new PageBlockJsonConverter());
    }
}
