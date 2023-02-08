<?php declare(strict_types = 1);

namespace Spot\SiteContent;

use jschreuder\Middle\View\RendererInterface;
use PDO;
use Psr\Http\Message\ResponseFactoryInterface;
use Spot\Application\View\JsonApiRenderer;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Serializer\PageBlockSerializer;
use Spot\SiteContent\Serializer\PageSerializer;

trait SiteContentServiceProvider
{
    abstract public function getHttpResponseFactory(): ResponseFactoryInterface;
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
        return new JsonApiRenderer($this->getHttpResponseFactory(), new PageSerializer());
    }

    public function getPageBlockRenderer(): RendererInterface
    {
        return new JsonApiRenderer($this->getHttpResponseFactory(), new PageBlockSerializer());
    }
}
