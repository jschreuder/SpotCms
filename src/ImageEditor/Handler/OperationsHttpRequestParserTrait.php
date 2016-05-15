<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Handler;

use Psr\Http\Message\ServerRequestInterface as ServerHttpRequest;
use Spot\Api\Request\Message\Request;
use Spot\Api\Request\RequestInterface;
use Spot\Application\Request\HttpRequestParserHelper;
use Spot\FileManager\FileManagerHelper;

trait OperationsHttpRequestParserTrait
{
    /** @var  FileManagerHelper */
    private $helper;

    public function parseHttpRequest(ServerHttpRequest $httpRequest, array $attributes) : RequestInterface
    {
        $rpHelper = new HttpRequestParserHelper($httpRequest);
        $filter = $rpHelper->getFilter();
        $validator = $rpHelper->getValidator();
        $this->helper->addPathFilter($filter, 'path');
        $this->helper->addFullPathValidator($validator, 'path');

        $data = $httpRequest->getQueryParams();
        $data['path'] = $attributes['path'];

        if (isset($data['operations']['resize'])) {
            $filter->values(['operations.resize.width', 'operations.resize.height'])->int();
            $validator->required('operations.resize.width')->integer();
            $validator->required('operations.resize.height')->integer();
        }
        if (isset($data['operations']['crop'])) {
            $filter->values(
                ['operations.crop.width', 'operations.crop.height', 'operations.crop.x', 'operations.crop.y']
            )->int();
            $validator->required('operations.crop.x')->integer();
            $validator->required('operations.crop.y')->integer();
            $validator->required('operations.crop.width')->integer();
            $validator->required('operations.crop.height')->integer();
        }
        if (isset($data['operations']['rotate'])) {
            $filter->value('operations.rotate.degrees')->int();
            $validator->required('operations.rotate.degrees')->integer();
        }
        if (isset($data['operations']['negative'])) {
            $filter->value('operations.negative.apply')->bool();
            $validator->required('operations.negative.apply')->bool();
        }
        if (isset($data['operations']['gamma'])) {
            $filter->value('operations.gamma.correction')->float();
            $validator->required('operations.gamma.correction')->numeric();
        }
        if (isset($data['operations']['greyscale'])) {
            $filter->value('operations.greyscale.apply')->bool();
            $validator->required('operations.greyscale.apply')->bool();
        }
        if (isset($data['operations']['blur'])) {
            $filter->value('operations.blur.amount')->float();
            $validator->required('operations.blur.amount')->numeric();
        }

        return new Request(self::MESSAGE, $rpHelper->filterAndValidate($data), $httpRequest);
    }
}
