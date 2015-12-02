<?php declare(strict_types=1);

namespace Spot\SiteContent\ApiCall;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\ApiCall\ApiCallInterface;
use Spot\Api\Http\JsonApiErrorResponse;
use Spot\Api\Http\JsonApiResponse;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Message\ArrayRequest;
use Spot\Api\Request\Message\BadRequest;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Request\RequestException;
use Spot\Api\Response\Message\ArrayResponse;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Common\ParticleFixes\Validator;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Serializer\PageSerializer;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Resource;

class GetPageApiCall implements ApiCallInterface
{
    use LoggableTrait;

    const MESSAGE = 'pages.get';

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
        $validator->required('uuid')->uuid();

        $validationResult = $validator->validate($attributes);
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

        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request->getData()['uuid']));
            return new ArrayResponse(self::MESSAGE, ['page' => $page]);
        } catch (NoUniqueResultException $e) {
            throw new ResponseException(new NotFoundResponse());
        }
    }

    public function generateResponse(ResponseInterface $response, HttpRequest $httpRequest) : HttpResponse
    {
        if (!$response instanceof ArrayResponse) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayResponse instance.');
            return new JsonApiErrorResponse('Server Error', 500);
        }

        $document = new Document(new Resource($response['page'], new PageSerializer()));
        return new JsonApiResponse($document);
    }
}
