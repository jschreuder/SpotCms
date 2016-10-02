<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Particle\Filter\Filter;
use Particle\Validator\Validator;

class CropOperation implements OperationInterface
{
    public function getName() : string
    {
        return 'crop';
    }

    public function addFilters(Filter $filter)
    {
        $filter->values(
            ['operations.crop.width', 'operations.crop.height', 'operations.crop.x', 'operations.crop.y']
        )->int();
    }

    public function addValidations(Validator $validator)
    {
        $validator->required('operations.crop.x')->integer();
        $validator->required('operations.crop.y')->integer();
        $validator->required('operations.crop.width')->integer();
        $validator->required('operations.crop.height')->integer();
    }
}
