<?php declare(strict_types = 1);

namespace Spot\FileManager\Serializer;

use Spot\FileManager\Entity\File;
use Tobscure\JsonApi\Relationship;
use Tobscure\JsonApi\SerializerInterface;

class FileSerializer implements SerializerInterface
{
    public function getType($model) : string
    {
        return File::TYPE;
    }

    public function getId($file) : string
    {
        if (!$file instanceof File) {
            throw new \InvalidArgumentException('FileSerializer can only serialize files.');
        }

        return $file->getUuid()->toString();
    }

    public function getAttributes($file, array $fields = null) : array
    {
        if (!$file instanceof File) {
            throw new \InvalidArgumentException('FileSerializer can only serialize files.');
        }

        return [
            'name' => $file->getName()->toString(),
            'path' => $file->getPath()->toString(),
            'mime_type' => $file->getMimeType()->toString(),
            'meta' => [
                'created' => $file->metaDataGetCreatedTimestamp()->format('c'),
                'updated' => $file->metaDataGetCreatedTimestamp()->format('c'),
            ],
        ];
    }

    public function getRelationship($file, $name) : Relationship
    {
        if (!$file instanceof File) {
            throw new \InvalidArgumentException('FileSerializer can only serialize files.');
        }

        throw new \OutOfBoundsException('Unknown relationship ' . $name . ' for ' . $this->getType($file));
    }

    public function getLinks($model)
    {
        return [];
    }

    public function getMeta($model)
    {
        return [];
    }
}
