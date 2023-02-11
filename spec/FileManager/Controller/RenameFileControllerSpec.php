<?php

namespace spec\Spot\FileManager\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\View\RendererInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Spot\Application\Http\JsonApiErrorResponse;
use Spot\Application\View\JsonApiView;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\FileManager\Entity\File;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Controller\RenameFileController;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Value\FileNameValue;

/** @mixin  RenameFileController */
class RenameFileControllerSpec extends ObjectBehavior
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
        $this->shouldHaveType(RenameFileController::class);
    }

    public function it_can_filter_a_request(ServerRequestInterface $request, ServerRequestInterface $request2, ServerRequestInterface $request3)
    {
        $query = ['path' => '/path/to/a/file.ext'];
        $request->getQueryParams()->willReturn($query);
        $request->withQueryParams($query)->willReturn($request2);

        $body = ['filename' => 'path.deux'];
        $request2->getParsedBody()->willReturn($body);
        $request2->withParsedBody($body)->willReturn($request3);

        $this->filterRequest($request)->shouldReturn($request3);
    }

    public function it_errors_on_invalid_path_when_validating_request(ServerRequestInterface $request)
    {
        $query = ['path' => '/'];
        $request->getQueryParams()->willReturn($query);
        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, File $file, ResponseInterface $response)
    {
        $query = ['path' => '/path/to/a/file.ext'];
        $request->getQueryParams()->willReturn($query);
        $body = ['filename' => 'path.deux'];
        $request->getParsedBody()->willReturn($body);

        $file->setName(FileNameValue::get($body['filename']))->shouldBeCalled();
        $this->fileRepository->getByFullPath($query['path'])->willReturn($file);
        $this->fileRepository->updateMetaData($file)->shouldBeCalled();

        $this->renderer->render($request, Argument::type(JsonApiView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
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
