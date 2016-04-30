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
use Spot\FileManager\Repository\FileRepository;

class DeleteFileHandler implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'files.delete';

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
        $validator->required('path')->lengthBetween(2, 192 + 96);

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
            $file = $this->fileRepository->getByFullPath($request['path']);
            $this->fileRepository->delete($file);
            return new Response(self::MESSAGE, ['data' => $file], $request);
        } catch (NoUniqueResultException $e) {
            return new NotFoundResponse([], $request);
        } catch (\Throwable $e) {
            $this->log(LogLevel::ERROR, $e->getMessage());
            throw new ResponseException(
                'An error occurred during DeleteFileHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }
}