<?php declare(strict_types = 1);

namespace Spot\FileManager\Serializer;

use Tobscure\JsonApi\SerializerInterface;

class DirectoryListingSerializer implements SerializerInterface
{
    public function getType($model)
    {
        return 'directoryListings';
    }

    public function getId($model)
    {
        return $model['path'];
    }

    public function getAttributes($model, array $fields = null)
    {
        return ['directories' => $model['directories'], 'files' => $model['files']];
    }

    public function getRelationship($model, $name)
    {
        throw new \OutOfBoundsException('Unknown relationship ' . $name . ' for ' . $this->getType($model));
    }
}
