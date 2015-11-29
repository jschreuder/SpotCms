<?php declare(strict_types=1);

namespace Spot\Api\Content\ApiCall;

use Particle\Validator\Validator;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\Application\ApiCallInterface;
use Spot\Api\Application\Request\Message\ArrayRequest;
use Spot\Api\Application\Request\Message\BadRequest;
use Spot\Api\Application\Request\Message\RequestInterface;
use Spot\Api\Application\Request\RequestException;
use Spot\Api\Application\Response\Message\ArrayResponse;
use Spot\Api\Application\Response\Message\ResponseInterface;
use Spot\Api\Common\Http\JsonApiResponse;
use Spot\Api\Common\LoggableTrait;
use Spot\Api\Content\Repository\PageRepository;
use Spot\Api\Content\Serializer\PageSerializer;
use Tobscure\JsonApi\Resource;
use Tobscure\JsonApi\Document;

class DeletePageApiCall implements ApiCallInterface
{
    use LoggableTrait;

    const MESSAGE = 'pages.delete';

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
            throw new RequestException(new BadRequest(), 400);
        }

        return new ArrayRequest(self::MESSAGE, $validationResult->getValues());
    }

    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
    {
        $page = $this->pageRepository->getByUuid(Uuid::fromString($request['uuid']));
        $this->pageRepository->delete($page);
        return new ArrayResponse(self::MESSAGE, ['page' => $page]);
    }

    public function generateResponse(ResponseInterface $response, HttpRequest $httpRequest) : HttpResponse
    {
        return new JsonApiResponse(new Document(new Resource($response['page'], new PageSerializer())), 200);
    }
}
