<?php declare(strict_types = 1);

namespace Spot\SiteContent\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\View\RendererInterface;
use Laminas\Filter\StringTrim;
use Laminas\Filter\ToInt;
use Laminas\I18n\Validator\IsInt;
use Laminas\Validator\Identical;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Laminas\Validator\Uuid as UuidValidator;
use Laminas\Validator\ValidatorChain;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\FilterService;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\ValidationService;
use Spot\Application\View\JsonView;
use Spot\DataModel\Repository\NoResultException;
use Spot\SiteContent\BlockType\BlockTypeContainerInterface;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class AddPageBlockController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
{
    public function __construct(
        private PageRepository $pageRepository,
        private BlockTypeContainerInterface $blockTypeContainer,
        private RendererInterface $renderer
    )
    {
    }

    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $request = FilterService::filterQuery($request, [
            'page_uuid' => new StringTrim(),
        ]);
        return FilterService::filter($request, [
            'data.attributes.type' => new StringTrim(),
            'data.attributes.location' => new StringTrim(),
            'data.attributes.sort_order' => new ToInt(),
        ]);
    }

    public function validateRequest(ServerRequestInterface $request): void
    {
        ValidationService::validateQuery($request, [
            'page_uuid' => new UuidValidator(),
        ]);
        ValidationService::validate($request, [
            'data.type' => new Identical('pageBlocks'),
            'data.attributes.type' => (new ValidatorChain())
                ->attach(new NotEmpty())
                ->attach(new StringLength(['min' => 1, 'max' => 48]))
                ->attach(new Regex(['pattern' => '#^[a-z0-9\-]+$#'])),
            'data.attributes.parameters' => new NotEmpty(),
            'data.attributes.location' => (new ValidatorChain())
                ->attach(new NotEmpty())
                ->attach(new StringLength(['min' => 1, 'max' => 48]))
                ->attach(new Regex(['pattern' => '#^[a-z0-9\-]+$#'])),
            'data.attributes.sort_order' => new IsInt(),
            'data.attributes.status' => new InArray(['haystack' => PageStatusValue::getValidStatuses()]),
        ], [
            'data.attributes.parameters',
        ]);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request->getQueryParams()['page_uuid']));
        } catch (NoResultException $exception) {
            return new JsonApiErrorResponse(['PAGE_NOT_FOUND' => 'Page not found'], 404);
        }

        $pageBlock = $this->createBlock($request->getParsedBody()['data']['attributes'], $page);
        $this->pageRepository->addBlockToPage($pageBlock, $page);
        return $this->renderer->render($request, new JsonView($pageBlock, false, [], 201));
    }

    private function createBlock(array $requestBody, Page $page): PageBlock
    {
        $blockType = $this->blockTypeContainer->getType($requestBody['type']);
        $pageBlock = $blockType->newBlock(
            $page,
            $requestBody['location'],
            $requestBody['sort_order'],
            PageStatusValue::get($requestBody['status'])
        );
        foreach ($requestBody['parameters'] as $parameter => $value) {
            $pageBlock[$parameter] = $value;
        }
        $blockType->validate($pageBlock);
        return $pageBlock;
    }
}
