<?php declare(strict_types = 1);

namespace Spot\FileManager\Handler;

use Particle\Filter\Filter;
use Particle\Validator\Validator;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Generator\GeneratorInterface;
use Spot\Api\Response\Http\JsonApiResponse;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\FileManager\Repository\FileRepository;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Resource;
use Tobscure\JsonApi\SerializerInterface;

class GetDirectoryListingHandler implements HttpRequestParserInterface, ExecutorInterface, GeneratorInterface
{
    use LoggableTrait;

    const MESSAGE = 'files.getDirectory';

    /** @var  FileRepository */
    private $fileRepository;

    public function __construct(FileRepository $fileRepository, LoggerInterface $logger)
    {
        $this->fileRepository = $fileRepository;
        $this->logger = $logger;
    }

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $filter = new Filter();
        $filter->values(['path'])
            ->string()
            ->trim(" \t\n\r\0\x0B/")
            ->prepend('/');

        $validator = new Validator();
        $validator->optional('path')->lengthBetween(1, 192 + 96);

        $data = $filter->filter($attributes);
        $validationResult = $validator->validate($data);
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult, $httpRequest);
        }

        return new Request(self::MESSAGE, $validationResult->getValues(), $httpRequest);
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            $path = $request['path'];
            $directories = $this->fileRepository->getDirectoriesInPath($path);
            $fileNames = $this->fileRepository->getFileNamesInPath($path);
            return new Response(self::MESSAGE, [
                'path' => $path,
                'directories' => $directories,
                'files' => $fileNames
            ], $request);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException(
                'An error occurred during GetDirectoryListingHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }

    public function generateResponse(ResponseInterface $response) : HttpResponse
    {
        $document = new Document(new Resource($response->getAttributes(), new class implements SerializerInterface {
            public function getType($model)
            {
                return 'directoryListings';
            }

            public function getId($model)
            {
                return $model['path'];
            }

            public function getAttributes($model, array $fields = null)
            {
                return $model;
            }

            public function getRelationship($model, $name)
            {
                throw new \OutOfBoundsException('Unknown relationship ' . $name . ' for ' . $this->getType($model));
            }
        }));
        return new JsonApiResponse($document);
    }
}
