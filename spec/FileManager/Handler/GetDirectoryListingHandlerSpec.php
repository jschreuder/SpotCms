<?php

namespace spec\Spot\FileManager\Handler;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Spot\Api\Request\RequestInterface;
use Spot\Api\Response\Http\JsonApiResponse;
use Spot\Api\Response\ResponseException;
use Spot\Api\Response\ResponseInterface;
use Spot\Application\Request\ValidationFailedException;
use Spot\FileManager\Handler\GetDirectoryListingHandler;
use Spot\FileManager\Repository\FileRepository;

/** @mixin  GetDirectoryListingHandler */
class GetDirectoryListingHandlerSpec extends ObjectBehavior
{
    /** @var  FileRepository */
    private $fileRepository;

    /** @var  LoggerInterface */
    private $logger;

    public function let(FileRepository $fileRepository, LoggerInterface $logger)
    {
        $this->fileRepository = $fileRepository;
        $this->logger = $logger;
        $this->beConstructedWith($fileRepository, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(GetDirectoryListingHandler::class);
    }

    public function it_can_parse_a_HttpRequest(ServerRequestInterface $httpRequest)
    {
        $path = '/path/to/';
        $attributes = ['path' => $path];

        $request = $this->parseHttpRequest($httpRequest, $attributes);
        $request->shouldHaveType(RequestInterface::class);
        $request->getRequestName()->shouldReturn(GetDirectoryListingHandler::MESSAGE);
        $request['path']->shouldBe(rtrim($attributes['path'], '/'));
    }

    public function it_errors_on_invalid_uuid_when_parsing_request(ServerRequestInterface $httpRequest)
    {
        $attributes = ['path' => str_repeat('a', 200)];
        $this->shouldThrow(ValidationFailedException::class)->duringParseHttpRequest($httpRequest, $attributes);
    }

    public function it_can_execute_a_request(RequestInterface $request)
    {
        $path = '/path/to';
        $directories = ['first', 'second'];
        $files = ['file.ext', 'about.txt'];
        $request->offsetGet('path')->willReturn($path);
        $request->getAcceptContentType()->willReturn('*/*');
        $this->fileRepository->getDirectoriesInPath($path)->willReturn($directories);
        $this->fileRepository->getFileNamesInPath($path)->willReturn($files);

        $response = $this->executeRequest($request);
        $response->shouldHaveType(ResponseInterface::class);
        $response->getResponseName()->shouldReturn(GetDirectoryListingHandler::MESSAGE);
        $response['path']->shouldBe($path);
        $response['directories']->shouldBe($directories);
        $response['files']->shouldBe($files);
    }

    public function it_can_handle_exception_during_request(RequestInterface $request)
    {
        $path = '/path/to/';
        $request->offsetGet('path')->willReturn($path);
        $request->getAcceptContentType()->willReturn('*/*');

        $this->fileRepository->getDirectoriesInPath($path)->willThrow(new \RuntimeException());

        $this->shouldThrow(ResponseException::class)->duringExecuteRequest($request);
    }

    public function it_can_generate_a_response(ResponseInterface $response)
    {
        $data = [
            'path' => '/path/to',
            'directories' => ['first', 'second'],
            'files' => ['file.ext', 'about.txt'],
        ];
        $response->getAttributes()->willReturn($data);

        $httpResponse = $this->generateResponse($response);
        $httpResponse->shouldHaveType(JsonApiResponse::class);
        $contents = $httpResponse->getBody()->getContents();
        $contents->shouldBe(json_encode([
            'data' => [
                'type' => 'directoryListings',
                'id' => $data['path'],
                'attributes' => [
                    'directories' => $data['directories'],
                    'files' => $data['files'],
                ],
            ],
        ], JsonApiResponse::DEFAULT_JSON_FLAGS));

    }
}
