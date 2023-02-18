<?php declare(strict_types = 1);

namespace Spot\Application\View;

use jschreuder\Middle\View\ViewInterface;

interface JsonViewInterface extends ViewInterface
{
    public function isCollection(): bool;

    public function getData(): mixed;

    public function setMetaData(string $key, $value): void;

    public function getMetaData(): array;
}
