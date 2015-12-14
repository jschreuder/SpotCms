<?php declare(strict_types=1);

namespace Spot\SiteContent\Handler;

use Particle\Filter\Filter;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Handler\RequestHandlerInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Common\ParticleFixes\Validator;
use Spot\Common\Request\ValidationFailedException;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class UpdatePageHandler implements RequestHandlerInterface
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
        $validator->optional('id')->uuid()->equals($attributes['uuid']);
        $validator->optional('attributes.title')->lengthBetween(1, 512);
        $validator->optional('attributes.slug')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->optional('attributes.short_title')->lengthBetween(1, 48);
        $validator->optional('attributes.sort_order')->integer();
        $validator->optional('attributes.status')
            ->inArray([PageStatusValue::CONCEPT, PageStatusValue::PUBLISHED], true);

        $data = $filter->filter($httpRequest->getParsedBody())['data'];
        $validationResult = $validator->validate($data);
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult, $httpRequest);
        }

        $request = new Request(self::MESSAGE, $validationResult->getValues()['attributes'], $httpRequest);
        $request['uuid'] = $attributes['uuid'];
        return $request;
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request['uuid']));
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
            return new Response(self::MESSAGE, ['data' => $page], $request);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException(
                'An error occurred during UpdatePageHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }
}
