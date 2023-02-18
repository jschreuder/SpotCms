<?php declare(strict_types = 1);

namespace Spot\SiteContent\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\View\RendererInterface;
use Laminas\Filter\FilterChain;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\I18n\Validator\IsInt;
use Laminas\Validator\Identical;
use Laminas\Validator\InArray;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Laminas\Validator\Uuid as UuidValidator;
use Laminas\Validator\ValidatorChain;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\FilterService;
use Spot\Application\ValidationService;
use Spot\Application\View\JsonView;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class UpdatePageController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
{
    public function __construct(
        private PageRepository $pageRepository,
        private RendererInterface $renderer
    )
    {
    }

    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        return FilterService::filter($request, [
            'data.attributes.title' => (new FilterChain())->attach(new StringTrim())->attach(new StripTags()),
            'data.attributes.slug' => (new FilterChain())->attach(new StringTrim())->attach(new StripTags()),
            'data.attributes.short_title' => (new FilterChain())->attach(new StringTrim())->attach(new StripTags()),
            'data.attributes.sort_order' => intval(...),
        ]);

    }

    public function validateRequest(ServerRequestInterface $request): void
    {
        ValidationService::validateQuery($request, [
            'page_uuid' => new UuidValidator(),
        ]);
        ValidationService::validate($request, [
            'data.type' => new Identical('pages'),
            'data.attributes.title' => new StringLength(['min' => 1, 'max' => 512]),
            'data.attributes.title' => (new ValidatorChain())
                ->attach(new StringLength(['min' => 1, 'max' => 48]))
                ->attach(new Regex(['pattern' => '#^[a-z0-9\-]+$#'])),
            'data.attributes.title' => new StringLength(['min' => 1, 'max' => 48]),
            'data.attributes.sort_order' => new IsInt(),
            'data.attributes.status' => new InArray(['haystack' => [PageStatusValue::CONCEPT, PageStatusValue::PUBLISHED]]),
        ], [
            'data.attributes.title',
            'data.attributes.slug',
            'data.attributes.short_title',
            'data.attributes.sort_order',
            'data.attributes.status',
        ]);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $pageUuid = $request->getQueryParams()['page_uuid'];
        $pageUpdates = $request->getParsedBody()['data']['attributes'];
        $page = $this->pageRepository->getByUuid(Uuid::fromString($pageUuid));
        
        $this->applyRequestToPage($pageUpdates, $page);
        $this->pageRepository->update($page);

        return $this->renderer->render($request, new JsonView($page));
    }

    private function applyRequestToPage(array $requestBody, Page $page): void
    {
        $this->setPageTitle($page, $requestBody);
        $this->setPageSlug($page, $requestBody);
        $this->setPageShortTitle($page, $requestBody);
        $this->setPageSortOrder($page, $requestBody);
        $this->setPageStatus($page, $requestBody);
    }

    private function setPageTitle(Page $page, array $requestBody): void
    {
        if (isset($requestBody['title'])) {
            $page->setTitle($requestBody['title']);
        }
    }

    private function setPageSlug(Page $page, array $requestBody): void
    {
        if (isset($requestBody['slug'])) {
            $page->setSlug($requestBody['slug']);
        }
    }

    private function setPageShortTitle(Page $page, array $requestBody): void
    {
        if (isset($requestBody['short_title'])) {
            $page->setShortTitle($requestBody['short_title']);
        }
    }

    private function setPageSortOrder(Page $page, array $requestBody): void
    {
        if (isset($requestBody['sort_order'])) {
            $page->setSortOrder($requestBody['sort_order']);
        }
    }

    private function setPageStatus(Page $page, array $requestBody): void
    {
        if (isset($requestBody['status'])) {
            $page->setStatus(PageStatusValue::get($requestBody['status']));
        }
    }
}
