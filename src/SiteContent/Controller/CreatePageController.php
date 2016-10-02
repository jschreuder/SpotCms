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

class CreatePageController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
{
    /** @var  PageRepository */
    private $pageRepository;

    /** @var  RendererInterface */
    private $renderer;

    public function __construct(PageRepository $pageRepository, RendererInterface $renderer)
    {
        $this->pageRepository = $pageRepository;
        $this->renderer = $renderer;
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
        $validator->required('data.type')->equals('pages');
        $validator->required('data.attributes.title')->lengthBetween(1, 512);
        $validator->required('data.attributes.slug')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->required('data.attributes.short_title')->lengthBetween(1, 48);
        $validator->optional('data.attributes.parent_uuid')->uuid();
        $validator->required('data.attributes.sort_order')->integer();
        $validator->required('data.attributes.status')->inArray(PageStatusValue::getValidStatuses(), true);

        $result = $validator->validate($request->getParsedBody());
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }

    /** {@inheritdoc} */
    public function execute(ServerRequestInterface $request) : ResponseInterface
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
        return $this->renderer->render($request, new JsonApiView($page));
    }
}
