<?php declare(strict_types = 1);

namespace Spot\Application\View;

use Spot\Application\JsonOutput\JsonConverterInterface;

final class JsonConverterRenderer extends JsonRenderer
{
    public function __construct(private JsonConverterInterface $jsonConverter)
    {
    }

    protected function getDataFromView(JsonViewInterface $view): array
    {
        $data = [
            'meta' => $view->getMetaData(),
        ];

        $entities = $view->getData();
        if (!$view->isCollection()) {
            $entities = [$entities];
        }
        foreach ($entities as $entity) {
            $data['data'][] = [
                'id' => $this->jsonConverter->getId($entity),
                'type' => $this->jsonConverter->getType($entity),
                'attributes' => $this->jsonConverter->getAttributes($entity),
                'relationships' => $this->jsonConverter->getRelationships($entity),
            ];
        }
        return $data;
    }
}
