<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Particle\Filter\Filter;
use Particle\Validator\Validator;

class BlurOperation implements OperationInterface
{
    public function getName() : string
    {
        return 'blur';
    }

    public function addFilters(Filter $filter)
    {
        $filter->value('operations.blur.amount')->float();
    }

    public function addValidations(Validator $validator)
    {
        $validator->required('operations.blur.amount')->numeric();
    }
}
