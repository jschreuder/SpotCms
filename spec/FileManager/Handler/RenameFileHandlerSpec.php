<?php

namespace spec\Spot\FileManager\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Message\NotFoundResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Handler\RenameFileHandler;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Value\FileNameValue;

/** @mixin  RenameFileHandler */
class RenameFileHandlerSpec extends ObjectBehavior
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
        $this->shouldHaveType(RenameFileHandler::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $path = '/path/to/a/file.ext';
        $filename = 'path.deux';
        $attributes = ['path' => $path];
        $httpRequest->getHeaderLine('Accept')->willReturn('*/*');
        $httpRequest->getParsedBody()->willReturn(['filename' => $filename]);

        $request = $this->parseHttpRequest($httpRequest, $attributes);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(RenameFileHandler::MESSAGE);
        $request['path']->shouldBe($attributes['path']);
        $request['filename']->shouldBe($filename);
    }

    public function it_errors_on_invalid_path_when_parsing_request(ServerRequestInterface $httpRequest)
    {
        $attributes = ['path' => '/'];
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, $attributes);
    }

    public function it_can_execute_a_request(RequestInterface $request, File $file)
    {
        $path = '/path/to/a/file.ext';
        $filename = 'path.deux';
        $request->offsetGet('path')->willReturn($path);
        $request->offsetGet('filename')->willReturn($filename);
        $request->getAcceptContentType()->willReturn('*/*');
        $file->setName(FileNameValue::get($filename))->shouldBeCalled();
        $this->fileRepository->getByFullPath($path)->willReturn($file);
        $this->fileRepository->updateMetaData($file)->shouldBeCalled();

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn(RenameFileHandler::MESSAGE);
        $response['data']->shouldBe($file);
    }

    public function it_can_execute_a_not_found_request(RequestInterface $request)
    {
        $path = '/path/to/a/file.ext';
        $request->offsetGet('path')->willReturn($path);
        $request->getAcceptContentType()->willReturn('*/*');

        $this->fileRepository->getByFullPath($path)->willThrow(new NoUniqueResultException());

        $response = $this->executeRequest($request);
        $response->shouldHaveType(NotFoundResponse::class);
    }

    public function it_can_handle_exception_during_request(RequestInterface $request)
    {
        $path = '/path/to/a/file.ext';
        $request->offsetGet('path')->willReturn($path);
        $request->getAcceptContentType()->willReturn('*/*');

        $this->fileRepository->getByFullPath($path)->willThrow(new \RuntimeException());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }
}
