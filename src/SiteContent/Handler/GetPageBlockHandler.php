<?php declare(strict_types = 1);

namespace Spot\SiteContent\Handler;

use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Application\Request\ValidationFailedException;
use Spot\Common\ParticleFixes\Validator;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\Repository\PageRepository;

class GetPageBlockHandler implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'pageBlocks.get';

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
        $validator->required('page_uuid')->uuid();
        $validator->required('uuid')->uuid();

        $validationResult = $validator->validate($attributes);
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult, $httpRequest);
        }

        return new Request(self::MESSAGE, $validationResult->getValues(), $httpRequest);
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request['page_uuid']));
            $block = $page->getBlockByUuid(Uuid::fromString($request['uuid']));
            return new Response(self::MESSAGE, ['data' => $block, 'includes' => ['pages']], $request);
        } catch (\Throwable $e) {
            if ($e instanceof NoUniqueResultException ) {
                return new NotFoundResponse([], $request);
            } elseif ($e instanceof \OutOfBoundsException) {
                return new NotFoundResponse([], $request);
            }

            $this->log(LogLevel::ERROR, $e->getMessage());
            throw new ResponseException(
                'An error occurred during GetPageBlockHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }
}
