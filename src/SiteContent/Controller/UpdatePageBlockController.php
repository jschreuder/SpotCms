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
use Spot\SiteContent\BlockType\BlockTypeContainerInterface;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class UpdatePageBlockController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
{
    /** @var  PageRepository */
    private $pageRepository;

    /** @var  BlockTypeContainerInterface */
    private $blockTypeContainer;

    /** @var  RendererInterface */
    private $renderer;

    public function __construct(
        PageRepository $pageRepository,
        BlockTypeContainerInterface $blockTypeContainer,
        RendererInterface $renderer
    )
    {
        $this->pageRepository = $pageRepository;
        $this->blockTypeContainer = $blockTypeContainer;
        $this->renderer = $renderer;
    }

    public function filterRequest(ServerRequestInterface $request) : ServerRequestInterface
    {
        $filter = new Filter();
        $filter->value('data.attributes.sort_order')->int();
        return $request->withParsedBody($filter->filter($request->getParsedBody()));

    }

    public function validateRequest(ServerRequestInterface $request)
    {
        $validator = new Validator();
        $validator->required('page_block_uuid')->uuid();
        $validator->required('page_uuid')->uuid();
        $validator->required('data.type')->equals('pageBlocks');
        $validator->optional('data.attributes.parameters');
        $validator->optional('data.attributes.sort_order')->integer();
        $validator->optional('data.attributes.status')
            ->inArray([PageStatusValue::CONCEPT, PageStatusValue::PUBLISHED], true);

        $data = $request->getParsedBody();
        $data['page_block_uuid'] = $request->getAttribute('page_block_uuid');
        $data['page_uuid'] = $request->getAttribute('page_uuid');

        $result = $validator->validate($data);
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        $page = $this->pageRepository->getByUuid(Uuid::fromString($request->getAttribute('page_uuid')));
        $block = $page->getBlockByUuid(Uuid::fromString($request->getAttribute('page_block_uuid')));
        $this->applyRequestToBlock($request->getParsedBody()['data']['attributes'], $block);
        $this->pageRepository->updateBlockForPage($block, $page);

        return $this->renderer->render($request, new JsonApiView($block));
    }

    private function applyRequestToBlock(array $requestBody, PageBlock $block)
    {
        $this->setBlockParameters($block, $requestBody);
        $this->setBlockSortOrder($block, $requestBody);
        $this->setBlockStatus($block, $requestBody);

        $blockType = $this->blockTypeContainer->getType($block->getType());
        $blockType->validate($block);
    }

    private function setBlockParameters(PageBlock $block, array $requestBody)
    {
        if (isset($requestBody['parameters'])) {
            foreach ($requestBody['parameters'] as $key => $value) {
                $block[$key] = $value;
            }
        }
    }

    private function setBlockSortOrder(PageBlock $block, array $requestBody)
    {
        if (isset($requestBody['sort_order'])) {
            $block->setSortOrder($requestBody['sort_order']);
        }
    }

    private function setBlockStatus(PageBlock $block, array $requestBody)
    {
        if (isset($requestBody['status'])) {
            $block->setStatus(PageStatusValue::get($requestBody['status']));
        }
    }
}
