<?php declare(strict_types=1);

namespace Spot\Api\Content\ApiCall;

use Particle\Filter\Filter;
use Particle\Validator\Validator;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\Application\ApiCallInterface;
use Spot\Api\Application\Request\Message\ArrayRequest;
use Spot\Api\Application\Request\Message\BadRequest;
use Spot\Api\Application\Request\Message\RequestInterface;
use Spot\Api\Application\Request\RequestException;
use Spot\Api\Application\Response\Message\ArrayResponse;
use Spot\Api\Application\Response\Message\ResponseInterface;
use Spot\Api\Application\Response\Message\ServerErrorResponse;
use Spot\Api\Application\Response\ResponseException;
use Spot\Api\Common\Http\JsonApiErrorResponse;
use Spot\Api\Common\Http\JsonApiResponse;
use Spot\Api\Common\LoggableTrait;
use Spot\Api\Content\Repository\PageRepository;
use Spot\Api\Content\Serializer\PageSerializer;
use Spot\Api\Content\Value\PageStatusValue;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Resource;

class UpdatePageApiCall implements ApiCallInterface
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
        $validator->required('id')->uuid();
        $validator->optional('attributes.title')->lengthBetween(1, 512);
        $validator->optional('attributes.slug')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->optional('attributes.short_title')->lengthBetween(1, 48);
        $validator->optional('attributes.sort_order')->integer();
        $validator->optional('attributes.status')
            ->inArray([PageStatusValue::CONCEPT, PageStatusValue::PUBLISHED], true);

        $data = $filter->filter($httpRequest->getParsedBody())['data'];
        $validationResult = $validator->validate($data);
        if ($validationResult->isNotValid()) {
            throw new RequestException(new BadRequest(), 400);
        }

        $request = new ArrayRequest(self::MESSAGE, $validationResult->getValues()['attributes']);
        $request['id'] = $data['id'];
        return $request;
    }

    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
    {
        if (!$request instanceof ArrayRequest) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayRequest instance.');
            throw new ResponseException(new ServerErrorResponse(), 500);
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
            return new ArrayResponse(self::MESSAGE, ['page' => $page]);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException(new ServerErrorResponse(), 500);
        }
    }

    public function generateResponse(ResponseInterface $response, HttpRequest $httpRequest) : HttpResponse
    {
        if (!$response instanceof ArrayResponse) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayResponse instance.');
            return new JsonApiErrorResponse(['error' => 'Server Error'], 500);
        }

        $document = new Document(new Resource($response['page'], new PageSerializer()));
        return new JsonApiResponse($document, 201);
    }
}
