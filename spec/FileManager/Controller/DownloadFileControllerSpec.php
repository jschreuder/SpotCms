<?php

namespace spec\Spot\FileManager\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Controller\DownloadFileController;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Value\FileNameValue;
use Spot\FileManager\Value\MimeTypeValue;

/** @mixin  DownloadFileController */
class DownloadFileControllerSpec extends ObjectBehavior
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
        $this->shouldHaveType(DownloadFileController::class);
    }

    public function it_can_filter_a_request(ServerRequestInterface $request, ServerRequestInterface $request2)
    {
        $query = ['path' => '/path/to/a/file.ext'];
        $request->getQueryParams()->willReturn($query);
        $request->withQueryParams($query)->willReturn($request2);

        $this->filterRequest($request)->shouldReturn($request2);
    }

    public function it_errors_on_invalid_path_when_validating_request(ServerRequestInterface $request)
    {
        $query = ['path' => '/'];
        $request->getQueryParams()->willReturn($query);
        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_can_execute_a_request(
        ServerRequestInterface $request,
        File $file,
        MimeTypeValue $mimeType,
        FileNameValue $fileName
    )
    {
        $query = ['path' => '/path/to/a/file.ext'];
        $request->getQueryParams()->willReturn($query);
        $this->fileRepository->getByFullPath($query['path'])->willReturn($file);

        $fileStream = $fp = fopen('php://memory', 'r+');
        $file->getStream()->willReturn($fileStream);
        $file->getMimeType()->willReturn($mimeType);
        $mimeType->toString()->willReturn('fake/mime-type');
        $file->getName()->willReturn($fileName);
        $fileName->toString()->willReturn('file.ext');

        $response = $this->execute($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getStatusCode()->shouldBe(200);
    }

    public function it_can_execute_a_not_found_request(ServerRequestInterface $request)
    {
        $query = ['path' => '/path/to/a/file.ext'];
        $request->getQueryParams()->willReturn($query);

        $this->fileRepository->getByFullPath($query['path'])->willThrow(new NoUniqueResultException());

        $response = $this->execute($request);
        $response->shouldHaveType(JsonApiErrorResponse::class);
    }
}
