<?php declare(strict_types = 1);

namespace Spot\Application\View;

use jschreuder\Middle\View\ViewInterface;

interface JsonApiViewInterface extends ViewInterface
{
    const CONTENT_TYPE_JSON_API = 'application/vnd.api+json';

    public function isCollection() : bool;

    public function getIncludes() : array;

    /** @return  mixed */
    public function getData();

    public function setMetaData(string $key, $value);

    public function getMetaData() : array;
}