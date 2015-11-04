<?php

namespace Spot\Cms\Application\Request;

interface RequestInterface
{
    /** @return  string */
    public function getName();

    /**
     * Must throw a RequestException on failure to validate the data
     *
     * @return  void
     * @throws  RequestException
     */
    public function validate();
}
