<?php declare(strict_types = 1);

namespace Spot\SiteContent\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Controller\ValidationFailedException;
use jschreuder\Middle\View\RendererInterface;
use Particle\Filter\Filter;
use Particle\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\View\JsonApiView;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class UpdatePageController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
{
    /** @var  PageRepository */
    private $pageRepository;

    /** @var  RendererInterface */
    private $renderer;

    public function __construct(PageRepository $pageRepository, RendererInterface $renderer)
    {
        $this->pageRepository = $pageRepository;
    }

    public function filterRequest(ServerRequestInterface $request) : ServerRequestInterface
    {
        $filter = new Filter();
        $filter->values(['data.attributes.title', 'data.attributes.slug', 'data.attributes.short_title'])
            ->trim()->stripHtml();
        $filter->value('data.attributes.sort_order')->int();
        return $request->withParsedBody($filter->filter($request->getParsedBody()));

    }

    public function validateRequest(ServerRequestInterface $request)
    {
        $validator = new Validator();
        $validator->required('page_uuid')->uuid();
        $validator->required('data.type')->equals('pages');
        $validator->optional('data.attributes.title')->lengthBetween(1, 512);
        $validator->optional('data.attributes.slug')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->optional('data.attributes.short_title')->lengthBetween(1, 48);
        $validator->optional('data.attributes.sort_order')->integer();
        $validator->optional('data.attributes.status')
            ->inArray([PageStatusValue::CONCEPT, PageStatusValue::PUBLISHED], true);

        $data = $request->getParsedBody();
        $data['page_uuid'] = $request->getAttribute('page_uuid');

        $result = $validator->validate($data);
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        $page = $this->pageRepository->getByUuid(Uuid::fromString($request->getAttribute('page_uuid')));
        $this->applyRequestToPage($request->getParsedBody()['data']['attributes'], $page);
        $this->pageRepository->update($page);

        return $this->renderer->render($request, new JsonApiView($page));
    }

    private function applyRequestToPage(array $requestBody, Page $page)
    {
        $this->setPageTitle($page, $requestBody);
        $this->setPageSlug($page, $requestBody);
        $this->setPageShortTitle($page, $requestBody);
        $this->setPageSortOrder($page, $requestBody);
        $this->setPageStatus($page, $requestBody);
    }

    private function setPageTitle(Page $page, array $requestBody)
    {
        if (isset($requestBody['title'])) {
            $page->setTitle($requestBody['title']);
        }
    }

    private function setPageSlug(Page $page, array $requestBody)
    {
        if (isset($requestBody['slug'])) {
            $page->setSlug($requestBody['slug']);
        }
    }

    private function setPageShortTitle(Page $page, array $requestBody)
    {
        if (isset($requestBody['short_title'])) {
            $page->setShortTitle($requestBody['short_title']);
        }
    }

    private function setPageSortOrder(Page $page, array $requestBody)
    {
        if (isset($requestBody['sort_order'])) {
            $page->setSortOrder($requestBody['sort_order']);
        }
    }

    private function setPageStatus(Page $page, array $requestBody)
    {
        if (isset($requestBody['status'])) {
            $page->setStatus(PageStatusValue::get($requestBody['status']));
        }
    }
}
