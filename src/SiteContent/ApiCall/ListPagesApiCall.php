<?php declare(strict_types=1);

namespace Spot\SiteContent\ApiCall;

use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParserInterface;
use Spot\Common\ParticleFixes\Validator;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\Http\JsonApiErrorResponse;
use Spot\Api\Http\JsonApiResponse;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Message\ArrayRequest;
use Spot\Api\Request\Message\BadRequest;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Request\RequestException;
use Spot\Api\Response\Message\ArrayResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Serializer\PageSerializer;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Document;

class ListPagesApiCall implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'pages.list';

    /** @var  PageRepository */
    private $pageRepository;

    public function __construct(PageRepository $pageRepository, LoggerInterface $logger)
    {
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
    }

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $validator = new Validator();
        $validator->optional('parent_uuid')->uuid();

        $validationResult = $validator->validate($httpRequest->getQueryParams());
        if ($validationResult->isNotValid()) {
            throw new RequestException(new BadRequest());
        }

        return new ArrayRequest(self::MESSAGE, $validationResult->getValues());
    }

    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
    {
        if (!$request instanceof ArrayRequest) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayRequest instance.');
            throw new ResponseException(new ServerErrorResponse());
        }

        $parentUuid = isset($request['parent_uuid']) ? Uuid::fromString($request['parent_uuid']) : null;
        return new ArrayResponse(self::MESSAGE, [
            'data' => $this->pageRepository->getAllByParentUuid($parentUuid),
            'parent_uuid' => $parentUuid,
        ]);
    }

    public function generateResponse(ResponseInterface $response, HttpRequest $httpRequest) : HttpResponse
    {
        if (!$response instanceof ArrayResponse) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayResponse instance.');
            return new JsonApiErrorResponse(['error' => 'Server Error'], 500);
        }

        $document = new Document(new Collection($response['data'], new PageSerializer()));
        $document->addMeta('parent_uuid', $response['parent_uuid'] ? $response['parent_uuid']->toString() : null);
        return new JsonApiResponse($document);
    }
}
