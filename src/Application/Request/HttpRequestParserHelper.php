<?php declare(strict_types = 1);

namespace Spot\Application\Request;

use Particle\Filter\Filter;
use Particle\Validator\Validator;
use Psr\Http\Message\ServerRequestInterface as HttpRequest;

class HttpRequestParserHelper
{
    /** @var  HttpRequest */
    private $httpRequest;

    /** @var  Validator */
    private $validator;

    /** @var  Filter */
    private $filter;

    public function __construct(HttpRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
        $this->validator = new Validator();
        $this->filter = new Filter();
    }

    public function getValidator() : Validator
    {
        return $this->validator;
    }

    public function getFilter() : Filter
    {
        return $this->filter;
    }

    public function filterAndValidate(array $data) : array
    {
        $data = $this->filter->filter($data);
        $validationResult = $this->validator->validate($data);
        if ($validationResult->isNotValid()) {
            throw new ValidationFailedException($validationResult, $this->httpRequest);
        }
        return $validationResult->getValues();
    }
}
