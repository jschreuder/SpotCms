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
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\View\JsonApiView;
use Spot\DataModel\Repository\NoResultException;
use Spot\SiteContent\BlockType\BlockTypeContainerInterface;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class AddPageBlockController implements RequestFilterInterface, RequestValidatorInterface, ControllerInterface
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
        $filter->values(['data.attributes.type', 'data.attributes.location'])->string()->trim();
        $filter->value('data.attributes.sort_order')->int();
        return $request->withParsedBody($filter->filter($request->getParsedBody()));
    }

    public function validateRequest(ServerRequestInterface $request)
    {
        $validator = new Validator();
        $validator->required('data.type')->equals('pageBlocks');
        $validator->required('data.attributes.type')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->optional('data.attributes.parameters');
        $validator->required('data.attributes.location')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->required('data.attributes.sort_order')->integer();
        $validator->required('data.attributes.status')->inArray(PageStatusValue::getValidStatuses(), true);

        $result = $validator->validate($request->getParsedBody());
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request->getAttribute('page_id')));
        } catch (NoResultException $exception) {
            return new JsonApiErrorResponse(['PAGE_NOT_FOUND' => 'Page not found'], 404);
        }

        $pageBlock = $this->createBlock($request->getParsedBody()['data']['attributes'], $page);
        $this->pageRepository->addBlockToPage($pageBlock, $page);
        return $this->renderer->render($request, new JsonApiView($pageBlock, false, ['pages'], [], 201));
    }

    private function createBlock(array $requestBody, Page $page) : PageBlock
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
