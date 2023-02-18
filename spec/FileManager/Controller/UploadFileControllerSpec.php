<?php

namespace spec\Spot\FileManager\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\View\RendererInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Spot\Application\View\JsonView;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Controller\UploadFileController;
use Spot\FileManager\Repository\FileRepository;

/** @mixin  UploadFileController */
class UploadFileControllerSpec extends ObjectBehavior
{
    /** @var  FileRepository */
    private $fileRepository;

    /** @var  FileManagerHelper */
    private $helper;

    /** @var  RendererInterface */
    private $renderer;

    public function let(FileRepository $fileRepository, RendererInterface $renderer)
    {
        $this->fileRepository = $fileRepository;
        $this->helper = new FileManagerHelper();
        $this->renderer = $renderer;
        $this->beConstructedWith($fileRepository, $this->helper, $renderer);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UploadFileController::class);
    }

    public function it_can_filter_a_request(ServerRequestInterface $request, ServerRequestInterface $request2)
    {
        $query = ['path' => '/path/To/a'];
        $request->getQueryParams()->willReturn($query);
        $request->withQueryParams($query)->willReturn($request2);

        $this->filterRequest($request)->shouldReturn($request2);
    }

    public function it_errors_on_invalid_path_when_validating_request(ServerRequestInterface $request, UploadedFileInterface $file)
    {
        $request->getQueryParams()->willReturn(['path' => '']);
        $files = [$file];
        $request->getUploadedFiles()->willReturn($files);

        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_errors_whithout_uploaded_files_when_validating_request(ServerRequestInterface $request)
    {
        $request->getQueryParams()->willReturn(['path' => '/path/To/a']);
        $request->getUploadedFiles()->willReturn([]);

        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, UploadedFileInterface $file, File $fileEntity, ResponseInterface $response)
    {
        $query = ['path' => '/path/To/a'];
        $file->getClientFilename()->willReturn($name = 'file.ext');
        $file->getClientMediaType()->willReturn($mime = 'text/xml');
        $file->getStream()->willReturn($stream = tmpfile());
        $files = [$file];

        $request->getQueryParams()->willReturn($query);
        $request->getUploadedFiles()->willReturn($files);
        $this->fileRepository->fromInput($name, $query['path'], $mime, $stream)->willReturn($fileEntity);
        $this->fileRepository->createFromUpload($fileEntity)->shouldBeCalled();

        $this->renderer->render($request, Argument::type(JsonView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
    }
}
