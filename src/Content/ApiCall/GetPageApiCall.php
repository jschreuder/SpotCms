<?php declare(strict_types=1);

namespace Spot\Api\Content\ApiCall;

use Spot\Api\Application\ApiCallInterface;
use Spot\Api\Common\LoggableTrait;

abstract class GetPageApiCall implements ApiCallInterface
{
    use LoggableTrait;

    const MESSAGE = 'pages.get';
}