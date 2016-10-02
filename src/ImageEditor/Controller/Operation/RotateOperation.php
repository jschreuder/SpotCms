<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Particle\Filter\Filter;
use Particle\Validator\Validator;

class RotateOperation implements OperationInterface
{
    public function getName() : string
    {
        return 'rotate';
    }

    public function addFilters(Filter $filter)
    {
        $filter->value('operations.rotate.degrees')->int();
    }

    public function addValidations(Validator $validator)
    {
        $validator->required('operations.rotate.degrees')->integer();
    }
}
