<?php

namespace Spot\Cms\Application\Request\Message;

use Spot\Cms\Application\Request\RequestException;

interface RequestInterface
{
    /** @return  string */
    public function getName();

    /**
     * MUST throw a RequestException on failure to validate the data, may not
     * throw any other type of Exception
     *
     * @return  void
     * @throws  RequestException
     */
    public function validate();
}
