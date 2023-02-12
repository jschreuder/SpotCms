<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Laminas\Filter\FilterInterface;
use Laminas\Validator\ValidatorInterface;

interface OperationInterface
{
    public function getName(): string;

    public function getFilters(): FilterInterface;

    public function getValidators(): ValidatorInterface;
}