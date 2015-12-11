<?php declare(strict_types=1);

namespace Spot\SiteContent\Handler;

use Particle\Filter\Filter;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Handler\RequestHandlerInterface;
use Spot\Api\Request\Message\ArrayRequest;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\ArrayResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Common\ParticleFixes\Validator;
use Spot\Common\Request\ValidationFailedException;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class UpdatePageBlockHandler implements RequestHandlerInterface
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
        $validator->required('type')->equals('pageBlocks');
        $validator->optional('id')->uuid()->equals($attributes['uuid']);
        $validator->optional('attributes.page_id')->uuid()->equals($attributes['page_uuid']);
        $validator->optional('attributes.parameters');
        $validator->optional('attributes.sort_order')->integer();
        $validator->optional('attributes.status')
            ->inArray([PageStatusValue::CONCEPT, PageStatusValue::PUBLISHED], true);

        $data = $filter->filter($httpRequest->getParsedBody())['data'];
        $validationResult = $validator->validate($data);
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult);
        }

        $request = new ArrayRequest(self::MESSAGE, $validationResult->getValues()['attributes']);
        $request['uuid'] = $attributes['uuid'];
        $request['page_uuid'] = $attributes['page_uuid'];
        return $request;
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        if (!$request instanceof ArrayRequest) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayRequest instance.');
            throw new ResponseException('An error occurred during UpdatePageBlockHandler.', new ServerErrorResponse());
        }

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
            return new ArrayResponse(self::MESSAGE, ['data' => $block]);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException('An error occurred during UpdatePageBlockHandler.', new ServerErrorResponse());
        }
    }
}
