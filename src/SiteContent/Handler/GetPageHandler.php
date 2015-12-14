<?php declare(strict_types = 1);

namespace Spot\SiteContent\Handler;

use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Handler\RequestHandlerInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Common\ParticleFixes\Validator;
use Spot\Common\Request\ValidationFailedException;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\SiteContent\Repository\PageRepository;

class GetPageHandler implements RequestHandlerInterface
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
            throw new ValidationFailedException($validationResult, $httpRequest);
        }

        return new Request(self::MESSAGE, $validationResult->getValues(), $httpRequest);
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            try {
                $page = $this->pageRepository->getByUuid(Uuid::fromString($request['uuid']));
                return new Response(self::MESSAGE, ['data' => $page, 'includes' => ['pageBlocks']], $request);
            } catch (NoUniqueResultException $e) {
                throw new ResponseException('Page not found.', new NotFoundResponse([], $request));
            }
        } catch (\Throwable $e) {
            $this->log(LogLevel::ERROR, $e->getMessage());
            throw new ResponseException(
                'An error occurred during GetPageHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }
}
