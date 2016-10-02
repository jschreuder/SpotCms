<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Particle\Filter\Filter;
use Particle\Validator\Validator;

class ResizeOperation implements OperationInterface
{
    public function getName() : string
    {
        return 'resize';
    }

    public function addFilters(Filter $filter)
    {
        $filter->values(['operations.resize.width', 'operations.resize.height'])->int();
    }

    public function addValidations(Validator $validator)
    {
        $validator->required('operations.resize.width')->integer();
        $validator->required('operations.resize.height')->integer();
    }
}
