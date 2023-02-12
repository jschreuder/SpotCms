<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Laminas\Filter\Callback as CallbackFilter;
use Laminas\Filter\FilterInterface;
use Laminas\Validator\Callback as CallbackValidator;
use Laminas\Validator\ValidatorInterface;

class GreyscaleOperation implements OperationInterface
{
    public function getName(): string
    {
        return 'greyscale';
    }

    public function getFilters(): FilterInterface
    {
        return new CallbackFilter(boolval(...));
    }

    public function getValidators(): ValidatorInterface
    {
        return new CallbackValidator(is_bool(...));
    }
}
