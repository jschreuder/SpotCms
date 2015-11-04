<?php

namespace Spot\Cms\Application\Request\Message;

use Spot\Cms\Application\Request\RequestException;

interface RequestInterface
{
    public function getName() : string;

    /**
     * MUST throw a RequestException on failure to validate the data, may not
     * throw any other type of Exception
     *
     * @throws  RequestException
     */
    public function validate();
}
