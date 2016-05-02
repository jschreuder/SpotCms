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
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\HttpRequestParserHelper;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class UpdatePageBlockHandler implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'pageBlocks.update';

    /** @var  PageRepository */
    private $pageRepository;

    public function __construct(PageRepository $pageRepository, LoggerInterface $logger)
    {
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
    }

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $rpHelper = new HttpRequestParserHelper($httpRequest);

        $rpHelper->getFilter()->value('data.attributes.sort_order')->int();

        $validator = $rpHelper->getValidator();
        $validator->required('data.type')->equals('pageBlocks');
        $validator->required('data.id')->uuid();
        $validator->required('data.attributes.page_uuid')->uuid();
        $validator->optional('data.attributes.parameters');
        $validator->optional('data.attributes.sort_order')->integer();
        $validator->optional('data.attributes.status')
            ->inArray([PageStatusValue::CONCEPT, PageStatusValue::PUBLISHED], true);

        $data = (array) $httpRequest->getParsedBody();
        $data['data']['id'] = $attributes['uuid'];
        $data['data']['attributes']['page_uuid'] = $attributes['page_uuid'];
        $request = new Request(self::MESSAGE, $rpHelper->filterAndValidate($data)['data']['attributes'], $httpRequest);
        $request['uuid'] = $data['data']['id'];
        return $request;
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request['page_uuid']));
            $block = $page->getBlockByUuid(Uuid::fromString($request['uuid']));
            if (isset($request['parameters'])) {
                foreach ($request['parameters'] as $key => $value) {
                    $block[$key] = $value;
                }
            }
            isset($request['sort_order']) && $block->setSortOrder($request['sort_order']);
            isset($request['status']) && $block->setStatus(PageStatusValue::get($request['status']));
            $this->pageRepository->updateBlockForPage($block, $page);
            return new Response(self::MESSAGE, ['data' => $block], $request);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException(
                'An error occurred during UpdatePageBlockHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }
}
