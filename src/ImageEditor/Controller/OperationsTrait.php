<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Spot\Application\FilterService;
use Spot\Application\ValidationService;
use Spot\FileManager\FileManagerHelper;

trait OperationsTrait
{
    private FileManagerHelper $helper;
    /** @var  OperationInterface[] */
    private array $operations;

    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $filters = ['path' => $this->helper->getPathFilter()];
        foreach ($this->operations as $operation) {
            $filters['operations.' . $operation->getName()] = $operation->getFilters();
        }

        return FilterService::filterQuery($request, $filters);
    }

    public function validateRequest(ServerRequestInterface $request): void
    {
        $validators = ['path' => $this->helper->getFullPathValidator()];
        $operationNames = [];
        foreach ($this->operations as $operation) {
            $validators['operations.' . $operation->getName()] = $operation->getValidators();
            $operationNames[] = 'operations.' . $operation->getName();
        }

        ValidationService::validateQuery($request, $validators, $operationNames);
    }
}
