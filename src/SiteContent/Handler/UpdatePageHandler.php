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
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class UpdatePageHandler implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'pages.update';

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

        $filter = $rpHelper->getFilter();
        $filter->values(['data.attributes.title', 'data.attributes.slug', 'data.attributes.short_title'])
            ->trim()->stripHtml();
        $filter->value('data.attributes.sort_order')->int();

        $validator = $rpHelper->getValidator();
        $validator->required('data.type')->equals('pages');
        $validator->required('data.id')->uuid();
        $validator->optional('data.attributes.title')->lengthBetween(1, 512);
        $validator->optional('data.attributes.slug')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->optional('data.attributes.short_title')->lengthBetween(1, 48);
        $validator->optional('data.attributes.sort_order')->integer();
        $validator->optional('data.attributes.status')
            ->inArray([PageStatusValue::CONCEPT, PageStatusValue::PUBLISHED], true);

        $data = (array) $httpRequest->getParsedBody();
        $data['data']['id'] = $attributes['uuid'];
        $request = new Request(self::MESSAGE, $rpHelper->filterAndValidate($data)['data'], $httpRequest);

        return $request;
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request['id']));
            $this->applyRequestToPage($request['attributes'], $page);
            $this->pageRepository->update($page);
            return new Response(self::MESSAGE, ['data' => $page], $request);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException(
                'An error occurred during UpdatePageHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }

    private function applyRequestToPage(array $requestAttributes, Page $page)
    {
        $this->setPageTitle($page, $requestAttributes);
        $this->setPageSlug($page, $requestAttributes);
        $this->setPageShortTitle($page, $requestAttributes);
        $this->setPageSortOrder($page, $requestAttributes);
        $this->setPageStatus($page, $requestAttributes);
    }

    private function setPageTitle(Page $page, array $requestAttributes)
    {
        if (isset($requestAttributes['title'])) {
            $page->setTitle($requestAttributes['title']);
        }
    }

    private function setPageSlug(Page $page, array $requestAttributes)
    {
        if (isset($requestAttributes['slug'])) {
            $page->setSlug($requestAttributes['slug']);
        }
    }

    private function setPageShortTitle(Page $page, array $requestAttributes)
    {
        if (isset($requestAttributes['short_title'])) {
            $page->setShortTitle($requestAttributes['short_title']);
        }
    }

    private function setPageSortOrder(Page $page, array $requestAttributes)
    {
        if (isset($requestAttributes['sort_order'])) {
            $page->setSortOrder($requestAttributes['sort_order']);
        }
    }

    private function setPageStatus(Page $page, array $requestAttributes)
    {
        if (isset($requestAttributes['status'])) {
            $page->setStatus(PageStatusValue::get($requestAttributes['status']));
        }
    }
}
