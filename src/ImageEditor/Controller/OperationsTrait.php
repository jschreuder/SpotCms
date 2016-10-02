<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller;

use jschreuder\Middle\Controller\ValidationFailedException;
use Particle\Filter\Filter;
use Particle\Validator\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Spot\FileManager\FileManagerHelper;

trait OperationsTrait
{
    /** @var  FileManagerHelper */
    private $helper;

    /** @var  Operation\OperationInterface[] */
    private $operations;

    public function filterRequest(ServerRequestInterface $request) : ServerRequestInterface
    {
        $filter = new Filter();
        $this->helper->addPathFilter($filter, 'path');
        foreach ($this->operations as $operation) {
            if (isset($data[$operation->getName()])) {
                $operation->addFilters($filter);
            }
        }

        $data = $filter->filter(array_merge($request->getQueryParams(), $request->getAttributes()));
        return $request->withAttribute('path', $data['path'])->withQueryParams($data);
    }

    public function validateRequest(ServerRequestInterface $request)
    {
        $validator = new Validator();
        $this->helper->addFullPathValidator($validator, 'path');
        foreach ($this->operations as $operation) {
            if (isset($data[$operation->getName()])) {
                $operation->addValidations($validator);
            }
        }

        $result = $validator->validate(array_merge($request->getQueryParams(), $request->getAttributes()));
        if (!$result->isValid()) {
            throw new ValidationFailedException($result->getMessages());
        }
    }
}
