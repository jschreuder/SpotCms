<?php declare(strict_types=1);

namespace Spot\SiteContent\ApiCall;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParserInterface;
use Spot\Api\Request\Message\ArrayRequest;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\ArrayResponse;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Common\ParticleFixes\Validator;
use Spot\Common\Request\ValidationFailedException;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\Repository\PageRepository;

class GetPageApiCall implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'pages.get';

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

        $validationResult = $validator->validate($attributes);
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult);
        }

        return new ArrayRequest(self::MESSAGE, $validationResult->getValues());
    }

    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
    {
        if (!$request instanceof ArrayRequest) {
            $msg = 'Did not receive an ArrayRequest instance.';
            $this->log(LogLevel::ERROR, $msg);
            throw new ResponseException($msg, new ServerErrorResponse());
        }

        try {
            $page = $this->pageRepository->getByUuid(Uuid::fromString($request->getData()['uuid']));
            return new ArrayResponse(self::MESSAGE, ['data' => $page]);
        } catch (NoUniqueResultException $e) {
            throw new ResponseException($e->getMessage(), new NotFoundResponse());
        }
    }
}
