<?php declare(strict_types = 1);

namespace Spot\FileManager\Handler;

use Particle\Filter\Filter;
use Particle\Validator\Validator;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Spot\Api\LoggableTrait;
use Spot\Api\Request\Executor\ExecutorInterface;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\Response;
use Spot\Api\Response\Message\ServerErrorResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Value\FileNameValue;
use Spot\FileManager\Value\FilePathValue;
use Spot\FileManager\Value\MimeTypeValue;

class UploadFileHandler implements HttpRequestParserInterface, ExecutorInterface
{
    use LoggableTrait;

    const MESSAGE = 'files.upload';

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

        $validator = new Validator();
        $this->helper->addPathValidator($validator, 'path');
        $validator->required('files')->callback(function ($array) { return is_array($array) && count($array) > 0; });

        $data = $filter->filter($attributes);
        $data['files'] = $httpRequest->getUploadedFiles();
        $validationResult = $validator->validate($data);
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult, $httpRequest);
        }

        return new Request(self::MESSAGE, $validationResult->getValues(), $httpRequest);
    }

    public function executeRequest(RequestInterface $request) : ResponseInterface
    {
        try {
            /** @var  UploadedFileInterface[] $uploadedFiles */
            $uploadedFiles = $request['files'];
            $path = $request['path'];

            $files = [];
            foreach ($uploadedFiles as $uploadedFile) {
                $files[] = $file = $this->createFile($uploadedFile, $path);
                $this->fileRepository->createFromUpload($file);
            }

            return new Response(self::MESSAGE, ['data' => $files], $request);
        } catch (\Throwable $exception) {
            $this->log(LogLevel::ERROR, $exception->getMessage());
            throw new ResponseException(
                'An error occurred during UploadFileHandler.',
                new ServerErrorResponse([], $request)
            );
        }
    }

    private function createFile(UploadedFileInterface $uploadedFile, string $path) : File
    {
        return new File(
            Uuid::uuid4(),
            FileNameValue::get(substr($uploadedFile->getClientFilename(), 0, 92)),
            FilePathValue::get($path),
            MimeTypeValue::get($uploadedFile->getClientMediaType()),
            $uploadedFile->getStream()
        );
    }
}
