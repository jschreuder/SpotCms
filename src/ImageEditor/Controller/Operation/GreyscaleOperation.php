<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Particle\Filter\Filter;
use Particle\Validator\Validator;

class GreyscaleOperation implements OperationInterface
{
    public function getName() : string
    {
        return 'greyscale';
    }

    public function addFilters(Filter $filter)
    {
        $filter->value('operations.greyscale.apply')->bool();
    }

    public function addValidations(Validator $validator)
    {
        $validator->required('operations.greyscale.apply')->bool();
    }
}
