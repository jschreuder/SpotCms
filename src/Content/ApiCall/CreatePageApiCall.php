<?php declare(strict_types=1);

namespace Spot\Api\Content\ApiCall;

use Particle\Filter\Filter;
use Spot\Api\Common\ParticleFixes\Validator;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
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
use Spot\Api\Content\Entity\Page;
use Spot\Api\Content\Repository\PageRepository;
use Spot\Api\Content\Serializer\PageSerializer;
use Spot\Api\Content\Value\PageStatusValue;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Resource;

class CreatePageApiCall implements ApiCallInterface
{
    use LoggableTrait;

    const MESSAGE = 'pages.create';

    /** @var  PageRepository */
    private $pageRepository;

    public function __construct(PageRepository $pageRepository, LoggerInterface $logger)
    {
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
    }

    /** {@inheritdoc} */
    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $filter = new Filter();
        $filter->values(['data.attributes.title', 'data.attributes.slug', 'data.attributes.short_title'])
            ->trim()->stripHtml();
        $filter->value('data.attributes.sort_order')->int();

        $validator = new Validator();
        $validator->required('data.type')->equals('pages');
        $validator->required('data.attributes.title')->lengthBetween(1, 512);
        $validator->required('data.attributes.slug')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->required('data.attributes.short_title')->lengthBetween(1, 48);
        $validator->optional('data.attributes.parent_uuid')->uuid();
        $validator->required('data.attributes.sort_order')->integer();
        $validator->required('data.attributes.status')->inArray(PageStatusValue::getValidStatuses(), true);

        $data = $filter->filter($httpRequest->getParsedBody());
        $validationResult = $validator->validate($data);
        if ($validationResult->isNotValid()) {
            throw new RequestException(new BadRequest());
        }

        return new ArrayRequest(self::MESSAGE, $validationResult->getValues()['data']['attributes']);
    }

    /** {@inheritdoc} */
    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
    {
        if (!$request instanceof ArrayRequest) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayRequest instance.');
            throw new ResponseException(new ServerErrorResponse());
        }

        try {
            $page = new Page(
                Uuid::uuid4(),
                $request['title'],
                $request['slug'],
                $request['short_title'],
                $request['parent_uuid'] ? Uuid::fromString($request['parent_uuid']) : null,
                $request['sort_order'],
                PageStatusValue::get($request['status'])
            );
            $this->pageRepository->create($page);
            return new ArrayResponse(self::MESSAGE, ['page' => $page]);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException(new ServerErrorResponse());
        }
    }

    /** {@inheritdoc} */
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
