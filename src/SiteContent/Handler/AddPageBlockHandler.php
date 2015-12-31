<?php declare(strict_types = 1);

namespace Spot\SiteContent\Handler;

use Particle\Filter\Filter;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Application\Request\ValidationFailedException;
use Spot\Common\ParticleFixes\Validator;
use Spot\DataModel\Repository\NoResultException;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

class AddPageBlockHandler implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'pageBlocks.create';

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
        $filter->values(['data.attributes.type', 'data.attributes.location'])->string()->trim();
        $filter->value('data.attributes.sort_order')->int();

        $validator = new Validator();
        $validator->required('data.type')->equals('pageBlocks');
        $validator->required('data.attributes.page_uuid')->uuid();
        $validator->required('data.attributes.type')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->optional('data.attributes.parameters');
        $validator->required('data.attributes.location')->lengthBetween(1, 48)->regex('#^[a-z0-9\-]+$#');
        $validator->required('data.attributes.sort_order')->integer();
        $validator->required('data.attributes.status')->inArray(PageStatusValue::getValidStatuses(), true);

        $parameters = (array) $httpRequest->getParsedBody();
        $parameters['data']['attributes']['page_uuid'] = $attributes['page_uuid'];
        $data = $filter->filter($parameters);
        $validationResult = $validator->validate($data);
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult, $httpRequest);
        }

        return new Request(self::MESSAGE, $validationResult->getValues()['data']['attributes'], $httpRequest);
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request['page_uuid']));
            $pageBlock = new PageBlock(
                Uuid::uuid4(),
                $page,
                $request['type'],
                $request['parameters'],
                $request['location'],
                $request['sort_order'],
                PageStatusValue::get($request['status'])
            );
            $this->pageRepository->addBlockToPage($pageBlock, $page);
            return new Response(self::MESSAGE, ['data' => $pageBlock, 'includes' => ['pages']], $request);
        } catch (NoResultException $exception) {
            return new NotFoundResponse([], $request);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException(
                'An error occurred during AddPageBlockHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }
}
