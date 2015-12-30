<?php declare(strict_types = 1);

namespace Spot\SiteContent\Handler;

use Particle\Filter\Filter;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Application\Request\ValidationFailedException;
use Spot\Common\ParticleFixes\Validator;
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
        $filter = new Filter();
        $filter->values(['attributes.title', 'attributes.slug', 'attributes.short_title'])
            ->trim()->stripHtml();
        $filter->value('attributes.sort_order')->int();

        $validator = new Validator();
        $validator->required('data.type')->equals('pageBlocks');
        $validator->required('data.id')->uuid()->equals($attributes['uuid']);
        $validator->required('data.attributes.page_id')->uuid();
        $validator->optional('data.attributes.parameters');
        $validator->optional('data.attributes.sort_order')->integer();
        $validator->optional('data.attributes.status')
            ->inArray([PageStatusValue::CONCEPT, PageStatusValue::PUBLISHED], true);

        $data = $filter->filter($httpRequest->getParsedBody());
        $request['data']['id'] = $attributes['uuid'];
        $request['data']['attributes']['page_uuid'] = $attributes['page_uuid'];
        $validationResult = $validator->validate($data);
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult, $httpRequest);
        }

        $request = new Request(self::MESSAGE, $validationResult->getValues()['data']['attributes'], $httpRequest);
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
            if (isset($request['sort_order'])) {
                $block->setSortOrder($request['sort_order']);
            }
            if (isset($request['status'])) {
                $block->setStatus(PageStatusValue::get($request['status']));
            }
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
