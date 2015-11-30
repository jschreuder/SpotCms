<?php declare(strict_types=1);

namespace Spot\Api\Content\ApiCall;

use Spot\Api\Common\ParticleFixes\Validator;
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
use Spot\Api\Application\Response\Message\NotFoundResponse;
use Spot\Api\Application\Response\Message\ResponseInterface;
use Spot\Api\Application\Response\Message\ServerErrorResponse;
use Spot\Api\Application\Response\ResponseException;
use Spot\Api\Common\Http\JsonApiErrorResponse;
use Spot\Api\Common\Http\JsonApiResponse;
use Spot\Api\Common\LoggableTrait;
use Spot\Api\Common\Repository\NoUniqueResultException;
use Spot\Api\Content\Repository\PageRepository;
use Spot\Api\Content\Serializer\PageSerializer;
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
