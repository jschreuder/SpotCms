<?php declare(strict_types = 1);

namespace Spot\FileManager;

use Laminas\Filter\FilterChain;
use Laminas\Filter\FilterInterface;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Laminas\Validator\ValidatorChain;
use Laminas\Validator\ValidatorInterface;

class FileManagerHelper
{
    const FILENAME_LENGTH = 64;
    const PATH_LENGTH = 128;
    const FULL_PATH_LENGTH = self::PATH_LENGTH + self::FILENAME_LENGTH;

    public function getPathFilter(): FilterInterface
    {
        return (new FilterChain())
            ->attach(strval(...))
            ->attach(new StripTags())
            ->attach(new StringTrim(" \t\n\r\0\x0B/"))
            ->attach(function($value) {
                return '/'.$value;
            });
    }

    public function getFileNameFilter(): FilterInterface
    {
        return (new FilterChain())
            ->attach(strval(...))
            ->attach(new StripTags())
            ->attach(new StringTrim(" \t\n\r\0\x0B/"))
            ->attach(function($value) {
                return '/'.$value;
            });
    }

    public function getPathValidator(int $length = self::PATH_LENGTH): ValidatorInterface
    {
        return (new ValidatorChain)
            ->attach(new NotEmpty())
            ->attach(new StringLength(['min' => 1, 'max' => $length]))
            ->attach(new Regex('#^[a-z0-9_/-]+$#uiD'));
    }

    public function getFileNameValidator(int $length = self::FILENAME_LENGTH)
    {
        return (new ValidatorChain)
            ->attach(new NotEmpty())
            ->attach(new StringLength(['min' => 1, 'max' => $length]))
            ->attach(new Regex('#^[a-z0-9_/\.-]+$#uiD'));
    }

    public function getFullPathValidator(int $length = self::FULL_PATH_LENGTH)
    {
        return (new ValidatorChain)
            ->attach(new NotEmpty())
            ->attach(new StringLength(['min' => 2, 'max' => $length]))
            ->attach(new Regex('#^[a-z0-9_/\.-]+$#uiD'));
    }
}
