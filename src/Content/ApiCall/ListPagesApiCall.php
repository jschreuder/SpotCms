<?php declare(strict_types=1);

namespace Spot\Api\Content\ApiCall;

use Particle\Validator\Validator;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Http\Message\RequestInterface as HttpRequest;
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
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Document;

class ListPagesApiCall implements ApiCallInterface
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
            throw new RequestException(new BadRequest(), 400);
        }

        return new ArrayRequest(self::MESSAGE, $validationResult->getValues());
    }

    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
    {
        if (!$request instanceof ArrayRequest) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayRequest instance.');
            throw new ResponseException(new ServerErrorResponse(), 500);
        }

        $parentUuid = $request['parent_uuid'] ? Uuid::fromString($request['parent_uuid']) : null;
        return new ArrayResponse(self::MESSAGE, [
            'pages' => $this->pageRepository->getAllByParentUuid($parentUuid),
            'parent_uuid' => $parentUuid,
        ]);
    }

    public function generateResponse(ResponseInterface $response, HttpRequest $httpRequest) : HttpResponse
    {
        if (!$response instanceof ArrayResponse) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayResponse instance.');
            return new JsonApiErrorResponse(['error' => 'Server Error'], 500);
        }

        $document = new Document(new Collection($response['pages'], new PageSerializer()));
        $document->addMeta('parent_uuid', $response['parent_uuid'] ? $response['parent_uuid']->toString() : null);
        return new JsonApiResponse($document);
    }
}
