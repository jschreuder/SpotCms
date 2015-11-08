<?php declare(strict_types=1);

namespace Spot\Api\Content\ApiCall;

use Particle\Filter\Filter;
use Particle\Validator\Validator;
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
use Spot\Api\Common\LoggableTrait;
use Spot\Api\Content\Entity\Page;
use Spot\Api\Content\Repository\PageRepository;
use Spot\Api\Content\Value\PageStatusValue;
use Zend\Diactoros\Response\JsonResponse;

class CreatePageApiCall implements ApiCallInterface
{
    use LoggableTrait;

    const MESSAGE = 'pages.request';

    /** @var  PageRepository */
    private $pageRepository;

    public function __construct(PageRepository $pageRepository, LoggerInterface $logger)
    {
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
    }

    /** {@inheritdoc} */
    public function parseHttpRequest(HttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $filter = new Filter();
        $filter->values(['title', 'slug', 'short_title'])->trim()->stripHtml();
        $filter->value('sort_order')->int();

        $validator = new Validator();
        $validator->required('title')->lengthBetween(1, 512);
        $validator->required('slug')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->required('short_title')->lengthBetween(1, 48);
        $validator->optional('parent_uuid')->uuid();
        $validator->required('sort_order')->integer();
        $validator->required('status')->inArray(PageStatusValue::getValidStatuses(), true);

        $data = $filter->filter($httpRequest->getParsedBody());
        $validationResult = $validator->validate($data);
        if ($validationResult->isNotValid()) {
            throw new RequestException(new BadRequest(), 400);
        }

        return new ArrayRequest(self::MESSAGE, $validationResult->getValues());
    }

    /** {@inheritdoc} */
    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
    {
        if (!$request instanceof ArrayRequest) {
            $this->log('Did not receive an ArrayRequest instance.', LogLevel::ERROR);
            throw new ResponseException(new ServerErrorResponse(), 500);
        }

        $data = $request->getData();
        try {
            $page = new Page(
                Uuid::uuid4(),
                $data['title'],
                $data['slug'],
                $data['short_title'],
                $data['parent_uuid'],
                $data['sort_order'],
                $data['status']
            );
            $this->pageRepository->create($page);
            return new ArrayResponse(self::MESSAGE, ['uuid' => $page->getUuid()->toString()]);
        } catch (\Throwable $exception) {
            $this->log($exception->getMessage(), LogLevel::ERROR);
            throw new ResponseException(new ServerErrorResponse(), 500);
        }
    }

    /** {@inheritdoc} */
    public function generateResponse(ResponseInterface $response, HttpRequest $httpRequest) : HttpResponse
    {
        if (!$response instanceof ArrayResponse) {
            $this->log('Did not receive an ArrayResponse instance.', LogLevel::ERROR);
            return new JsonResponse(['error' => 'Server Error'], 500);
        }

        return new JsonResponse($response->getData(), 201);
    }
}
