<?php declare(strict_types = 1);

namespace Spot\SiteContent\Handler;

use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Handler\ParseAndExecuteHandlerInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Common\ParticleFixes\Validator;
use Spot\Common\Request\ValidationFailedException;
use Spot\SiteContent\Repository\PageRepository;

class ListPagesHandler implements \Spot\Api\Handler\ParseAndExecuteHandlerInterface
{
    use LoggableTrait;

    const MESSAGE = 'pages.list';

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
        $validator->optional('parent_uuid')->uuid();

        $validationResult = $validator->validate($httpRequest->getQueryParams());
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult, $httpRequest);
        }

        return new Request(self::MESSAGE, $validationResult->getValues(), $httpRequest);
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $parentUuid = isset($request['parent_uuid']) ? Uuid::fromString($request['parent_uuid']) : null;
            return new Response(self::MESSAGE, [
                'data' => $this->pageRepository->getAllByParentUuid($parentUuid),
                'parent_uuid' => $parentUuid,
                'includes' => ['pageBlocks'],
            ], $request);
        } catch (\Throwable $e) {
            $this->log(LogLevel::ERROR, $e->getMessage());
            throw new ResponseException(
                'An error occurred during ListPagesHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }
}
