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
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\HttpRequestParserHelper;
use Spot\DataModel\Repository\NoResultException;
use Spot\SiteContent\Repository\PageRepository;

class DeletePageBlockHandler implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'pageBlocks.delete';

    /** @var  PageRepository */
    private $pageRepository;

    public function __construct(PageRepository $pageRepository, LoggerInterface $logger)
    {
        $this->pageRepository = $pageRepository;
        $this->logger = $logger;
    }

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $rpHelper = new HttpRequestParserHelper($httpRequest);

        $validator = $rpHelper->getValidator();
        $validator->required('uuid')->uuid();
        $validator->required('page_uuid')->uuid();

        return new Request(self::MESSAGE, $rpHelper->filterAndValidate($attributes), $httpRequest);
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request['page_uuid']));
            $pageBlock = $page->getBlockByUuid(Uuid::fromString($request['uuid']));
            $this->pageRepository->deleteBlockFromPage($pageBlock, $page);
            return new Response(self::MESSAGE, ['data' => $pageBlock], $request);
        } catch (NoResultException $e) {
            return new NotFoundResponse([], $request);
        } catch (\Throwable $e) {
            $this->log(LogLevel::ERROR, $e->getMessage());
            throw new ResponseException(
                'An error occurred during DeletePageBlockHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }
}
