<?php declare(strict_types = 1);

namespace Spot\Application;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Http\Factory\RequestFactoryInterface;
use Psr\Http\Factory\ResponseFactoryInterface;
use Psr\Http\Factory\ServerRequestFactoryInterface;
use Psr\Http\Factory\ServerRequestFromGlobalsFactoryInterface;
use Psr\Http\Factory\StreamFactoryInterface;
use Psr\Http\Factory\UploadedFileFactoryInterface;
use Psr\Http\Factory\UriFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Stream;
use Zend\Diactoros\UploadedFile;
use Zend\Diactoros\Uri;

class HttpFactoryProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['http.request_factory'] = function () {
            return new class implements RequestFactoryInterface {
                public function createRequest($method, $uri) : RequestInterface
                {
                    return new Request($uri, $method);
                }
            };
        };

        $container['http.server_request_factory'] = function () {
            return new class implements ServerRequestFactoryInterface, ServerRequestFromGlobalsFactoryInterface {
                public function createServerRequest($method, $uri) : ServerRequestInterface
                {
                    return new ServerRequest([], [], $uri, $method);
                }

                public function createServerRequestFromGlobals() : ServerRequestInterface
                {
                    return ServerRequestFactory::fromGlobals();
                }
            };
        };

        $container['http.response_factory'] = function () {
            return new class implements ResponseFactoryInterface {
                public function createResponse($code = 200) : ResponseInterface
                {
                    return new Response('php://memory', intval($code));
                }
            };
        };

        $container['http.stream_factory'] = function () {
            return new class implements StreamFactoryInterface {
                public function createStream($resource)
                {
                    return new Stream($resource);
                }
            };
        };

        $container['http.uri_factory'] = function () {
            return new class implements UriFactoryInterface {
                public function createUri($uri = '')
                {
                    return new Uri($uri);
                }
            };
        };

        $container['http.uploaded_file_factory'] = function () {
            return new class  implements UploadedFileFactoryInterface {
                public function createUploadedFile(
                    $file,
                    $size = null,
                    $error = \UPLOAD_ERR_OK,
                    $clientFilename = null,
                    $clientMediaType = null
                ) {
                    if ($size === null) {
                        if (is_string($file)) {
                            $size = filesize($file);
                        } else {
                            $stats = fstat($file);
                            $size = $stats['size'];
                        }
                    }
                    return new UploadedFile($file, $size, $error, $clientFilename, $clientMediaType);
                }
            };
        };
    }
}
