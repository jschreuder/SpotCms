<?php

namespace spec\Spot\FileManager\Handler;

use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Controller\UploadFileController;
use Spot\FileManager\Repository\FileRepository;

/** @mixin  UploadFileController */
class UploadFileHandlerSpec extends ObjectBehavior
{
    /** @var  FileRepository */
    private $fileRepository;

    /** @var  FileManagerHelper */
    private $helper;

    /** @var  LoggerInterface */
    private $logger;

    public function let(FileRepository $fileRepository, LoggerInterface $logger)
    {
        $this->fileRepository = $fileRepository;
        $this->helper = new FileManagerHelper();
        $this->logger = $logger;
        $this->beConstructedWith($fileRepository, $this->helper, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UploadFileController::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest, UploadedFileInterface $file)
    {
        $path = '/path/To/a';
        $attributes = ['path' => $path];
        $files = [$file];

        $httpRequest->getUploadedFiles()->willReturn($files);
        $httpRequest->getHeaderLine('Accept')->willReturn('application/json');

        $request = $this->parseHttpRequest($httpRequest, $attributes);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(UploadFileController::MESSAGE);
        $request['path']->shouldBe($attributes['path']);
    }

    public function it_errors_on_invalid_path_when_parsing_request(ServerRequestInterface $httpRequest)
    {
        $attributes = ['path' => '/dir'];
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, $attributes);
    }

    public function it_can_execute_a_request(RequestInterface $request, UploadedFileInterface $file, File $fileEntity)
    {
        $path = '/path/To/a';
        $file->getClientFilename()->willReturn($name = 'file.ext');
        $file->getClientMediaType()->willReturn($mime = 'text/xml');
        $file->getStream()->willReturn($stream = tmpfile());
        $files = [$file];

        $request->offsetGet('path')->willReturn($path);
        $request->offsetGet('files')->willReturn($files);
        $request->getAcceptContentType()->willReturn('application/json');
        $this->fileRepository->fromInput($name, $path, $mime, $stream)->willReturn($fileEntity);
        $this->fileRepository->createFromUpload($fileEntity)->shouldBeCalled();

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn(UploadFileController::MESSAGE);
        $response['data'][0]->shouldHaveType(File::class);
    }

    public function it_can_handle_exception_during_request(RequestInterface $request, UploadedFileInterface $file)
    {
        $path = '/path/To/a';
        $file->getClientFilename()->willReturn($name = 'file.ext');
        $file->getClientMediaType()->willReturn($mime = 'text/xml');
        $file->getStream()->willReturn($stream = tmpfile());
        $files = [$file];

        $request->offsetGet('path')->willReturn($path);
        $request->offsetGet('files')->willReturn($files);
        $request->getAcceptContentType()->willReturn('application/json');
        $this->fileRepository->fromInput($name, $path, $mime, $stream)
            ->willThrow(new \RuntimeException());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
