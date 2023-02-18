<?php declare(strict_types = 1);

namespace Spot\SiteContent\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\View\RendererInterface;
use Laminas\I18n\Validator\IsInt;
use Laminas\Validator\Identical;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Uuid as UuidValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Spot\Application\FilterService;
use Spot\Application\ValidationService;
use Spot\Application\View\JsonView;
use Spot\SiteContent\BlockType\BlockTypeContainerInterface;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class UpdatePageBlockController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
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
        return FilterService::filter($request, [
            'data.attributes.sort_order' => intval(...),
        ]);

    }

    public function validateRequest(ServerRequestInterface $request): void
    {
        ValidationService::validateQuery($request, [
            'page_block_uuid' => new UuidValidator(),
            'page_uuid' => new UuidValidator(),
        ]);
        ValidationService::validate($request, [
            'data.type' => new Identical('pageBlocks'),
            'data.attributes.parameters' => new NotEmpty(),
            'data.attributes.sort_order' => new IsInt(),
            'data.attributes.status' => new InArray(['haystack' => [PageStatusValue::CONCEPT, PageStatusValue::PUBLISHED]])
        ], [
            'data.attributes.parameters',
            'data.attributes.sort_order',
            'data.attributes.status',
        ]);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $pageUuid = $request->getQueryParams()['page_uuid'];
        $page = $this->pageRepository->getByUuid(Uuid::fromString($pageUuid));
        $pageBlockUuid = $request->getQueryParams()['page_block_uuid'];
        $block = $page->getBlockByUuid(Uuid::fromString($pageBlockUuid));
        $pageBlockUpdates = $request->getParsedBody()['data']['attributes'];

        $this->applyRequestToBlock($pageBlockUpdates, $block);
        $this->pageRepository->updateBlockForPage($block, $page);

        return $this->renderer->render($request, new JsonView($block));
    }

    private function applyRequestToBlock(array $requestBody, PageBlock $block): void
    {
        $this->setBlockParameters($block, $requestBody);
        $this->setBlockSortOrder($block, $requestBody);
        $this->setBlockStatus($block, $requestBody);

        $blockType = $this->blockTypeContainer->getType($block->getType());
        $blockType->validate($block);
    }

    private function setBlockParameters(PageBlock $block, array $requestBody): void
    {
        if (isset($requestBody['parameters'])) {
            foreach ($requestBody['parameters'] as $key => $value) {
                $block[$key] = $value;
            }
        }
    }

    private function setBlockSortOrder(PageBlock $block, array $requestBody): void
    {
        if (isset($requestBody['sort_order'])) {
            $block->setSortOrder($requestBody['sort_order']);
        }
    }

    private function setBlockStatus(PageBlock $block, array $requestBody): void
    {
        if (isset($requestBody['status'])) {
            $block->setStatus(PageStatusValue::get($requestBody['status']));
        }
    }
}
