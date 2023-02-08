<?php declare(strict_types = 1);

namespace Spot\SiteContent;

use jschreuder\Middle\View\RendererInterface;
use PDO;
use Psr\Http\Message\ResponseFactoryInterface;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\SiteContent\Repository\PageRepository;

interface SiteContentServiceProviderInterface
{
    public function config(string $valueName): mixed;

    public function getHttpResponseFactory(): ResponseFactoryInterface;

    public function getDatabase(): PDO;

    public function getObjectRepository(): ObjectRepository;

    public function getPageRepository(): PageRepository;

    public function getPageRenderer(): RendererInterface;

    public function getPageBlockRenderer(): RendererInterface;
}
