<?php declare(strict_types = 1);

namespace Spot\SiteContent;

use jschreuder\Middle\View\RendererInterface;
use Neomerx\JsonApi\Encoder\Encoder;
use PDO;
use Psr\Http\Message\ResponseFactoryInterface;
use Spot\Application\View\JsonApiRenderer;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Schema\PageBlockSchema;
use Spot\SiteContent\Schema\PageSchema;

trait SiteContentServiceProvider
{
    abstract public function config(string $valueName): mixed;
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

    public function getSiteContentRenderer(): RendererInterface
    {
        return new JsonApiRenderer($this->getHttpResponseFactory(), Encoder::instance([
                Page::class => PageSchema::class,
                PageBlock::class => PageBlockSchema::class,
            ])->withUrlPrefix($this->config('site.url'))
        );
    }
}
