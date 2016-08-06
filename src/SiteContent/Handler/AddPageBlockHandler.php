<?php declare(strict_types = 1);

namespace Spot\SiteContent\Handler;

use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\HttpRequestParserHelper;
use Spot\DataModel\Repository\NoResultException;
use Spot\SiteContent\BlockType\BlockTypeContainerInterface;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class AddPageBlockHandler implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'pageBlocks.create';

    /** @var  PageRepository */
    private $pageRepository;

    /** @var  BlockTypeContainerInterface */
    private $blockTypeContainer;

    public function __construct(
        PageRepository $pageRepository,
        BlockTypeContainerInterface $blockTypeContainer,
        LoggerInterface $logger
    )
    {
        $this->pageRepository = $pageRepository;
        $this->blockTypeContainer = $blockTypeContainer;
        $this->logger = $logger;
    }

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $rpHelper = new HttpRequestParserHelper($httpRequest);

        $filter = $rpHelper->getFilter();
        $filter->values(['data.attributes.type', 'data.attributes.location'])->string()->trim();
        $filter->value('data.attributes.sort_order')->int();

        $validator = $rpHelper->getValidator();
        $validator->required('data.id')->uuid();
        $validator->required('data.type')->equals('pageBlocks');
        $validator->required('data.attributes.type')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->optional('data.attributes.parameters');
        $validator->required('data.attributes.location')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->required('data.attributes.sort_order')->integer();
        $validator->required('data.attributes.status')->inArray(PageStatusValue::getValidStatuses(), true);

        $parameters = (array) $httpRequest->getParsedBody();
        $parameters['data']['id'] = $attributes['page_uuid'];
        return new Request(
            self::MESSAGE,
            $rpHelper->filterAndValidate($parameters)['data'],
            $httpRequest
        );
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request['id']));
            $pageBlock = $this->createBlockFromRequest($request, $page);
            $this->pageRepository->addBlockToPage($pageBlock, $page);
            return new Response(self::MESSAGE, ['data' => $pageBlock, 'includes' => ['pages']], $request);
        } catch (NoResultException $exception) {
            return new NotFoundResponse([], $request);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException(
                'An error occurred during AddPageBlockHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }

    private function createBlockFromRequest(RequestInterface $request, Page $page) : PageBlock
    {
        $requestAttributes = $request['attributes'];
        $blockType = $this->blockTypeContainer->getType($requestAttributes['type']);
        $pageBlock = $blockType->newBlock(
            $page,
            $requestAttributes['location'],
            $requestAttributes['sort_order'],
            PageStatusValue::get($requestAttributes['status'])
        );
        foreach ($requestAttributes['parameters'] as $parameter => $value) {
            $pageBlock[$parameter] = $value;
        }
        $blockType->validate($pageBlock, $request);
        return $pageBlock;
    }
}
