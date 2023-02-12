<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Laminas\Filter\Callback as CallbackFilter;
use Laminas\Filter\FilterInterface;
use Laminas\I18n\Validator\IsFloat;
use Laminas\Validator\ValidatorInterface;

class BlurOperation implements OperationInterface
{
    public function getName(): string
    {
        return 'blur';
    }

    public function getFilters(): FilterInterface
    {
        return new CallbackFilter(floatval(...));
    }

    public function getValidators(): ValidatorInterface
    {
        return new IsFloat();
    }
}
