<?php declare(strict_types = 1);

namespace Spot\SiteContent\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\View\RendererInterface;
use Laminas\Filter\FilterChain;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\Filter\ToInt;
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

class CreatePageController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
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
            'data.attributes.title' => (new FilterChain())
                ->attach(new StringTrim())
                ->attach(new StripTags()),
            'data.attributes.slug' => (new FilterChain())
                ->attach(new StringTrim())
                ->attach(new StripTags()),
            'data.attributes.short_title' => (new FilterChain())
                ->attach(new StringTrim())
                ->attach(new StripTags()),
            'data.attributes.sort_order' => new ToInt(),
        ]);
    }

    public function validateRequest(ServerRequestInterface $request): void
    {
        ValidationService::validate($request, [
            'data.type' => new Identical('pages'),
            'data.attributes.title' => new StringLength(['min' => 1, 'max' => 512]),
            'data.attributes.slug' => (new ValidatorChain())
                ->attach(new StringLength(['min' => 1, 'max' => 48]))
                ->attach(new Regex(['pattern' => '#^[a-z0-9\-]+$#'])),
            'data.attributes.short_title' => new StringLength(['min' => 1, 'max' => 48]),
            'data.attributes.parent_uuid' => new UuidValidator(),
            'data.attributes.sort_order' => new IsInt(),
            'data.attributes.status' => new InArray(['haystack' => PageStatusValue::getValidStatuses()]),
        ]);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody()['data']['attributes'];
        $page = new Page(
            Uuid::uuid4(),
            $data['title'],
            $data['slug'],
            $data['short_title'],
            $data['parent_uuid'] ? Uuid::fromString($data['parent_uuid']) : null,
            $data['sort_order'],
            PageStatusValue::get($data['status'])
        );
        $this->pageRepository->create($page);
        return $this->renderer->render($request, new JsonView($page, false, [], 201));
    }
}
