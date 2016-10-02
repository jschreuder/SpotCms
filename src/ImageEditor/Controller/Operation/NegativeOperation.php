<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Particle\Filter\Filter;
use Particle\Validator\Validator;

class NegativeOperation implements OperationInterface
{
    public function getName() : string
    {
        return 'negative';
    }

    public function addFilters(Filter $filter)
    {
        $filter->value('operations.negative.apply')->bool();
    }

    public function addValidations(Validator $validator)
    {
        $validator->required('operations.negative.apply')->bool();
    }
}
