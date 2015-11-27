<?php declare(strict_types=1);

namespace Spot\Api\Content\ApiCall;

use Spot\Api\Application\ApiCallInterface;

abstract class ListPagesApiCall implements ApiCallInterface
{
    const MESSAGE = 'pages.list';
}