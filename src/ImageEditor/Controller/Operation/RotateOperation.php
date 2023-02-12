<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Laminas\Filter\Callback as CallbackFilter;
use Laminas\Filter\FilterInterface;
use Laminas\I18n\Validator\IsInt;
use Laminas\Validator\ValidatorInterface;

class RotateOperation implements OperationInterface
{
    public function getName() : string
    {
        return 'rotate';
    }

    public function getFilters(): FilterInterface
    {
        return new CallbackFilter(intval(...));
    }

    public function getValidators(): ValidatorInterface
    {
        return new IsInt();
    }
}
