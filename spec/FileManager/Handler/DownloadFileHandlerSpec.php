<?php

namespace spec\Spot\FileManager\Handler;

use PhpSpec\ObjectBehavior;
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
use Spot\FileManager\Handler\DownloadFileHandler;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Value\FileNameValue;
use Spot\FileManager\Value\MimeTypeValue;

/** @mixin  DownloadFileHandler */
class DownloadFileHandlerSpec extends ObjectBehavior
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
        $this->shouldHaveType(DownloadFileHandler::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $path = '/path/to/a/file.ext';
        $attributes = ['path' => $path];

        $request = $this->parseHttpRequest($httpRequest, $attributes);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(DownloadFileHandler::MESSAGE);
        $request['path']->shouldBe($attributes['path']);
    }

    public function it_errors_on_invalid_path_when_parsing_request(ServerRequestInterface $httpRequest)
    {
        $attributes = ['path' => '/'];
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, $attributes);
    }

    public function it_can_execute_a_request(RequestInterface $request, File $file)
    {
        $path = '/path/to/a/file.ext';
        $request->offsetGet('path')->willReturn($path);
        $request->getAcceptContentType()->willReturn('*/*');
        $this->fileRepository->getByFullPath($path)->willReturn($file);

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn(DownloadFileHandler::MESSAGE);
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

    public function it_can_generate_a_response(ResponseInterface $response, File $file)
    {
        $fileName = 'file.ext';
        $mimeType = 'text/xml';
        $file->getStream()->willReturn(tmpfile());
        $file->getName()->willReturn(FileNameValue::get($fileName));
        $file->getMimeType()->willReturn(MimeTypeValue::get($mimeType));
        $response->offsetGet('data')->willReturn($file);

        $httpResponse = $this->generateResponse($response);
        $httpResponse->getHeaderLine('Content-Type')->shouldReturn($mimeType);
        $httpResponse->getHeaderLine('Content-Disposition')->shouldReturn('attachment; filename="' . $fileName . '"');
    }
}
