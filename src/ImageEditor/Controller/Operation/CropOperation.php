<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller\Operation;

use Laminas\Filter\Callback as CallbackFilter;
use Laminas\Filter\FilterInterface;
use Laminas\Validator\Callback as CallbackValidator;
use Laminas\Validator\ValidatorInterface;

class CropOperation implements OperationInterface
{
    public function getName(): string
    {
        return 'crop';
    }

    public function getFilters(): FilterInterface
    {
        return new CallbackFilter(function ($value) {
            if (!is_array($value)) {
                return null;
            }

            if (isset($value['width'])) {
                $value['width'] = intval($value['width']);
            }
            if (isset($value['height'])) {
                $value['height'] = intval($value['height']);
            }
            if (isset($value['x'])) {
                $value['x'] = intval($value['x']);
            }
            if (isset($value['y'])) {
                $value['y'] = intval($value['y']);
            }

            return $value;
        });
    }

    public function getValidators(): ValidatorInterface
    {
        return new CallbackValidator(function ($value) {
            if (
                !is_array($value)
                || !isset($value['width'])
                || !isset($value['height'])
                || !isset($value['x'])
                || !isset($value['y'])
            ) {
                return false;
            }
            return true;
        });
    }
}
