<?php declare(strict_types=1);

namespace Spot\SiteContent\ApiCall;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParserInterface;
use Spot\Api\Request\Message\ArrayRequest;
use Spot\Api\Request\Message\BadRequest;
use Spot\Api\Request\Message\RequestInterface;
use Spot\Api\Request\RequestException;
use Spot\Api\Response\Message\ArrayResponse;
use Spot\Api\Response\Message\ResponseInterface;
use Spot\Common\ParticleFixes\Validator;
use Spot\SiteContent\Repository\PageRepository;

class DeletePageApiCall implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'pages.delete';

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
            throw new RequestException(new BadRequest());
        }

        return new ArrayRequest(self::MESSAGE, $validationResult->getValues());
    }

    public function executeRequest(RequestInterface $request, HttpRequest $httpRequest) : ResponseInterface
    {
        $page = $this->pageRepository->getByUuid(Uuid::fromString($request['uuid']));
        $this->pageRepository->delete($page);
        return new ArrayResponse(self::MESSAGE, ['data' => $page]);
    }
}
