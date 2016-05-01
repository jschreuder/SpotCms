<?php declare(strict_types = 1);

namespace Spot\FileManager;

use Particle\Filter\Filter;
use Particle\Validator\Validator;

class FileManagerHelper
{
    const FILENAME_LENGTH = 96;
    const PATH_LENGTH = 192;
    const FULL_PATH_LENGTH = self::PATH_LENGTH + self::FILENAME_LENGTH;

    public function addPathFilter(Filter $filter, string $name)
    {
        $filter->value($name)
            ->string()
            ->trim(" \t\n\r\0\x0B/")
            ->prepend('/');
    }

    public function addPathValidator(Validator $validator, string $name, $length = self::PATH_LENGTH)
    {
        $validator->required($name)
            ->lengthBetween(1, $length);
    }

    public function addFileNameFilter(Filter $filter, string $name)
    {
        $filter->value($name)
            ->string()
            ->trim(" \t\n\r\0\x0B/");
    }

    public function addFileNameValidator(Validator $validator, string $name, $length = self::FILENAME_LENGTH)
    {
        $validator->required($name)
            ->lengthBetween(1, $length);
    }

    public function addFullPathValidator(Validator $validator, string $name, $length = self::FULL_PATH_LENGTH)
    {
        $validator->required($name)
            ->lengthBetween(2, $length);
    }
}
