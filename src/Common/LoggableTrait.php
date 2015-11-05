<?php

namespace Spot\Cms\Common;

use Psr\Log\LoggerInterface;

trait LoggableTrait
{
    /** @var  LoggerInterface */
    private $logger;

    protected function log(string $message, string $level, array $metadata = [])
    {
        $this->logger->log($level, '[' . get_class($this) . '] ' . $message, $metadata);
    }
}
