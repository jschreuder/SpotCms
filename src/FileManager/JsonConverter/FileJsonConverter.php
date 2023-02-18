<?php declare(strict_types = 1);

namespace Spot\FileManager\JsonConverter;

use Spot\Application\JsonConverter\JsonConverterInterface;
use Spot\FileManager\Entity\File;

class FileJsonConverter implements JsonConverterInterface
{
    public function getType(): string
    {
        return File::TYPE;
    }

    public function getId($file): string
    {
        if (!$file instanceof File) {
            throw new \InvalidArgumentException('FileSchema can only work on files.');
        }

        return $file->getUuid()->toString();
    }

    public function getAttributes($file): array
    {
        if (!$file instanceof File) {
            throw new \InvalidArgumentException('FileSchema can only work on files.');
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

    public function getRelationships($file): array
    {
        if (!$file instanceof File) {
            throw new \InvalidArgumentException('FileSchema can only work on files.');
        }

        return [];
    }
}
