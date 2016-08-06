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

        $data = $filter->filter((array) $httpRequest->getParsedBody());
        $data['data']['id'] = $attributes['uuid'];
        $request = new Request(self::MESSAGE, $rpHelper->filterAndValidate($data)['data']['attributes'], $httpRequest);
        $request['uuid'] = $data['data']['id'];
        return $request;
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request['uuid']));
            $this->applyRequestToPage($request, $page);
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

    private function applyRequestToPage(RequestInterface $request, Page $page)
    {
        $this->setPageTitle($page, $request);
        $this->setPageSlug($page, $request);
        $this->setPageShortTitle($page, $request);
        $this->setPageSortOrder($page, $request);
        $this->setPageStatus($page, $request);
    }

    private function setPageTitle(Page $page, RequestInterface $request)
    {
        if (isset($request['title'])) {
            $page->setTitle($request['title']);
        }
    }

    private function setPageSlug(Page $page, RequestInterface $request)
    {
        if (isset($request['slug'])) {
            $page->setSlug($request['slug']);
        }
    }

    private function setPageShortTitle(Page $page, RequestInterface $request)
    {
        if (isset($request['short_title'])) {
            $page->setShortTitle($request['short_title']);
        }
    }

    private function setPageSortOrder(Page $page, RequestInterface $request)
    {
        if (isset($request['sort_order'])) {
            $page->setSortOrder($request['sort_order']);
        }
    }

    private function setPageStatus(Page $page, RequestInterface $request)
    {
        if (isset($request['status'])) {
            $page->setStatus(PageStatusValue::get($request['status']));
        }
    }
}
