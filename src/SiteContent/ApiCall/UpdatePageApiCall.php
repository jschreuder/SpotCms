<?php declare(strict_types=1);

namespace Spot\SiteContent\ApiCall;

use Particle\Filter\Filter;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParserInterface;
use Spot\Api\Request\Message\ArrayRequest;
use Spot\Api\Request\Message\BadRequest;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Request\RequestException;
use Spot\Api\Response\Message\ArrayResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Common\ParticleFixes\Validator;
use Spot\Common\Request\ValidationFailedException;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class UpdatePageApiCall implements HttpRequestParserInterface, ExecutorInterface
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
        $filter = new Filter();
        $filter->values(['attributes.title', 'attributes.slug', 'attributes.short_title'])
            ->trim()->stripHtml();
        $filter->value('attributes.sort_order')->int();

        $validator = new Validator();
        $validator->required('type')->equals('pages');
        $validator->required('id')->uuid()->equals($attributes['uuid']);
        $validator->optional('attributes.title')->lengthBetween(1, 512);
        $validator->optional('attributes.slug')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->optional('attributes.short_title')->lengthBetween(1, 48);
        $validator->optional('attributes.sort_order')->integer();
        $validator->optional('attributes.status')
            ->inArray([PageStatusValue::CONCEPT, PageStatusValue::PUBLISHED], true);

        $data = $filter->filter($httpRequest->getParsedBody())['data'];
        $validationResult = $validator->validate($data);
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult);
        }

        $request = new ArrayRequest(self::MESSAGE, $validationResult->getValues()['attributes']);
        $request['id'] = $attributes['uuid'];
        return $request;
    }

    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
    {
        if (!$request instanceof ArrayRequest) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayRequest instance.');
            throw new ResponseException('An error occurred during UpdatePageApiCall.', new ServerErrorResponse());
        }

        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request['id']));
            if (isset($request['title'])) {
                $page->setTitle($request['title']);
            }
            if (isset($request['slug'])) {
                $page->setSlug($request['slug']);
            }
            if (isset($request['short_title'])) {
                $page->setShortTitle($request['short_title']);
            }
            if (isset($request['sort_order'])) {
                $page->setSortOrder($request['sort_order']);
            }
            if (isset($request['status'])) {
                $page->setStatus(PageStatusValue::get($request['status']));
            }
            $this->pageRepository->update($page);
            return new ArrayResponse(self::MESSAGE, ['data' => $page]);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException('An error occurred during UpdatePageApiCall.', new ServerErrorResponse());
        }
    }
}
