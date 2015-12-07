<?php declare(strict_types=1);

namespace Spot\SiteContent\Handler;

use Particle\Filter\Filter;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Handler\RequestHandlerInterface;
use Spot\Api\Request\Message\ArrayRequest;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\ArrayResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Common\ParticleFixes\Validator;
use Spot\Common\Request\ValidationFailedException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class CreatePageHandler implements RequestHandlerInterface
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
            throw new ValidationFailedException($validationResult);
        }

        return new ArrayRequest(self::MESSAGE, $validationResult->getValues()['data']['attributes']);
    }

    /** {@inheritdoc} */
    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
    {
        if (!$request instanceof ArrayRequest) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayRequest instance.');
            throw new ResponseException('An error occurred during CreatePageHandler.', new ServerErrorResponse());
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
            return new ArrayResponse(self::MESSAGE, ['data' => $page]);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException('An error occurred during CreatePageHandler.', new ServerErrorResponse());
        }
    }
}
