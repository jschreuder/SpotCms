<?php declare(strict_types = 1);

namespace Spot\FileManager\Handler;

use Particle\Filter\Filter;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
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
use Spot\Application\Request\ValidationFailedException;
use Spot\Common\ParticleFixes\Validator;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Value\FileNameValue;
use Spot\FileManager\Value\FilePathValue;

class MoveFileHandler implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'files.move';

    /** @var  FileRepository */
    private $fileRepository;

    /** @var  FileManagerHelper */
    private $helper;

    public function __construct(FileRepository $fileRepository, FileManagerHelper $helper, LoggerInterface $logger)
    {
        $this->fileRepository = $fileRepository;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $filter = new Filter();
        $this->helper->addPathFilter($filter, 'path');
        $this->helper->addPathFilter($filter, 'new_path');

        $validator = new Validator();
        $this->helper->addFullPathValidator($validator, 'path');
        $this->helper->addPathValidator($validator, 'new_path');

        $validationResult = $validator->validate($filter->filter([
            'path' => $attributes['path'],
            'new_path' => $httpRequest->getParsedBody()['new_path'],
        ]));
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult, $httpRequest);
        }

        return new Request(self::MESSAGE, $validationResult->getValues(), $httpRequest);
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $file = $this->fileRepository->getByFullPath($request['path']);
            $file->setPath(FilePathValue::get($request['new_path']));
            $this->fileRepository->updateMetaData($file);
            return new Response(self::MESSAGE, ['data' => $file], $request);
        } catch (NoUniqueResultException $e) {
            return new NotFoundResponse([], $request);
        } catch (\Throwable $e) {
            $this->log(LogLevel::ERROR, $e->getMessage());
            throw new ResponseException(
                'An error occurred during MoveFileHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }
}
