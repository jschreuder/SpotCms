<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Particle\Filter\Filter;
use Particle\Validator\Validator;

interface OperationInterface
{
    public function getName() : string;

    /** @return  void */
    public function addFilters(Filter $filter);

    /** @return  void */
    public function addValidations(Validator $validator);
}