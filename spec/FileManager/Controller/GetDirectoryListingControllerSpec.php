<?php

namespace spec\Spot\FileManager\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\View\RendererInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spot\Application\View\JsonView;
use Spot\FileManager\FileManagerHelper;
use Spot\FileManager\Controller\GetDirectoryListingController;
use Spot\FileManager\Repository\FileRepository;

/** @mixin  GetDirectoryListingController */
class GetDirectoryListingControllerSpec extends ObjectBehavior
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
        $this->shouldHaveType(GetDirectoryListingController::class);
    }

    public function it_can_filter_a_request(ServerRequestInterface $request, ServerRequestInterface $request2)
    {
        $query = ['path' => '/path/to'];
        $request->getQueryParams()->willReturn($query);
        $request->withQueryParams($query)->willReturn($request2);

        $this->filterRequest($request)->shouldReturn($request2);
    }

    public function it_errors_on_invalid_uuid_when_validating_request(ServerRequestInterface $request)
    {
        $request->getQueryParams()->willReturn(['path' => str_repeat('a', 200)]);
        $this->shouldThrow(ValidationFailedException::class)->duringValidateRequest($request);
    }

    public function it_can_execute_a_request(ServerRequestInterface $request, ResponseInterface $response)
    {
        $query = ['path' => '/path/to'];
        $directories = ['first', 'second'];
        $files = ['file.ext', 'about.txt'];
        $request->getQueryParams()->willReturn($query);

        $this->fileRepository->getDirectoriesInPath($query['path'])->willReturn($directories);
        $this->fileRepository->getFileNamesInPath($query['path'])->willReturn($files);

        $this->renderer->render($request, Argument::type(JsonView::class))->willReturn($response);

        $this->execute($request)->shouldReturn($response);
    }
}
