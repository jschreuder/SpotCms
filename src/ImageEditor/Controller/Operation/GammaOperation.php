<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Particle\Filter\Filter;
use Particle\Validator\Validator;

class GammaOperation implements OperationInterface
{
    public function getName() : string
    {
        return 'gamma';
    }

    public function addFilters(Filter $filter)
    {
        $filter->value('operations.gamma.correction')->float();
    }

    public function addValidations(Validator $validator)
    {
        $validator->required('operations.gamma.correction')->numeric();
    }
}
