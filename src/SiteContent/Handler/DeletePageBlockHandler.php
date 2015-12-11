<?php declare(strict_types=1);

namespace Spot\SiteContent\Handler;

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

class DeletePageBlockHandler implements RequestHandlerInterface
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
        $validator = new Validator();
        $validator->required('uuid')->uuid();
        $validator->required('page_uuid')->uuid();

        $validationResult = $validator->validate($attributes);
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult);
        }

        return new ArrayRequest(self::MESSAGE, $validationResult->getValues());
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        if (!$request instanceof ArrayRequest) {
            $this->log(LogLevel::ERROR, 'Did not receive an ArrayRequest instance.');
            throw new ResponseException('An error occurred during DeletePageBlockHandler.', new ServerErrorResponse());
        }

        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request['page_uuid']));
            $pageBlock = $page->getBlockByUuid(Uuid::fromString($request['uuid']));
            $this->pageRepository->deleteBlockFromPage($pageBlock, $page);
            return new ArrayResponse(self::MESSAGE, ['data' => $pageBlock]);
        } catch (\Throwable $e) {
            $this->log(LogLevel::ERROR, $e->getMessage());
            throw new ResponseException('An error occurred during DeletePageBlockHandler.', new ServerErrorResponse());
        }
    }
}
