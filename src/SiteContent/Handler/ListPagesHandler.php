<?php declare(strict_types=1);

namespace Spot\SiteContent\Handler;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
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
use Spot\SiteContent\Repository\PageRepository;

class ListPagesHandler implements RequestHandlerInterface
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
            throw new ValidationFailedException($validationResult);
        }

        return new ArrayRequest(self::MESSAGE, $validationResult->getValues());
    }

    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
    {
        if (!$request instanceof ArrayRequest) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayRequest instance.');
            throw new ResponseException('An error occurred during ListPagesHandler.', new ServerErrorResponse());
        }

        try {
            $parentUuid = isset($request['parent_uuid']) ? Uuid::fromString($request['parent_uuid']) : null;
            return new ArrayResponse(self::MESSAGE, [
                'data' => $this->pageRepository->getAllByParentUuid($parentUuid),
                'parent_uuid' => $parentUuid,
                'includes' => ['pageBlocks'],
            ]);
        } catch (\Throwable $e) {
            $this->log(LogLevel::ERROR, $e->getMessage());
            throw new ResponseException('An error occurred during ListPagesHandler.', new ServerErrorResponse());
        }
    }
}
