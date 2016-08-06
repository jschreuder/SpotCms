<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Handler;

use Particle\Filter\Filter;
use Particle\Validator\Validator;
use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Spot\Api\Request\HttpRequestParser\HttpRequestParserInterface;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestInterface;
use Spot\Application\Request\HttpRequestParserHelper;
use Spot\FileManager\FileManagerHelper;

class OperationsHttpRequestParser implements HttpRequestParserInterface
{
    /** @var  FileManagerHelper */
    private $helper;

    /** @var  string */
    private $messageName;

    public function __construct(string $messageName, FileManagerHelper $helper)
    {
        $this->messageName = $messageName;
        $this->helper = $helper;
    }

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $rpHelper = new HttpRequestParserHelper($httpRequest);
        $filter = $rpHelper->getFilter();
        $validator = $rpHelper->getValidator();
        $this->helper->addPathFilter($filter, 'path');
        $this->helper->addFullPathValidator($validator, 'path');

        $data = $httpRequest->getQueryParams();
        $data['path'] = $attributes['path'];

        $this->parseOperationResize($validator, $filter, $data);
        $this->parseOperationCrop($validator, $filter, $data);
        $this->parseOperationRotate($validator, $filter, $data);
        $this->parseOperationNegative($validator, $filter, $data);
        $this->parseOperationGamma($validator, $filter, $data);
        $this->parseOperationGreyscale($validator, $filter, $data);
        $this->parseOperationBlur($validator, $filter, $data);

        return new Request($this->messageName, $rpHelper->filterAndValidate($data), $httpRequest);
    }

    private function parseOperationResize(Validator $validator, Filter $filter, array $data)
    {
        if (isset($data['operations']['resize'])) {
            $filter->values(['operations.resize.width', 'operations.resize.height'])->int();
            $validator->required('operations.resize.width')->integer();
            $validator->required('operations.resize.height')->integer();
        }
    }

    private function parseOperationCrop(Validator $validator, Filter $filter, array $data)
    {
        if (isset($data['operations']['crop'])) {
            $filter->values(
                ['operations.crop.width', 'operations.crop.height', 'operations.crop.x', 'operations.crop.y']
            )->int();
            $validator->required('operations.crop.x')->integer();
            $validator->required('operations.crop.y')->integer();
            $validator->required('operations.crop.width')->integer();
            $validator->required('operations.crop.height')->integer();
        }
    }

    private function parseOperationRotate(Validator $validator, Filter $filter, array $data)
    {
        if (isset($data['operations']['rotate'])) {
            $filter->value('operations.rotate.degrees')->int();
            $validator->required('operations.rotate.degrees')->integer();
        }
    }

    private function parseOperationNegative(Validator $validator, Filter $filter, array $data)
    {
        if (isset($data['operations']['negative'])) {
            $filter->value('operations.negative.apply')->bool();
            $validator->required('operations.negative.apply')->bool();
        }
    }

    private function parseOperationGamma(Validator $validator, Filter $filter, array $data)
    {
        if (isset($data['operations']['gamma'])) {
            $filter->value('operations.gamma.correction')->float();
            $validator->required('operations.gamma.correction')->numeric();
        }
    }

    private function parseOperationGreyscale(Validator $validator, Filter $filter, array $data)
    {
        if (isset($data['operations']['greyscale'])) {
            $filter->value('operations.greyscale.apply')->bool();
            $validator->required('operations.greyscale.apply')->bool();
        }
    }

    private function parseOperationBlur(Validator $validator, Filter $filter, array $data)
    {
        if (isset($data['operations']['blur'])) {
            $filter->value('operations.blur.amount')->float();
            $validator->required('operations.blur.amount')->numeric();
        }
    }
}
