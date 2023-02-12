<?php declare(strict_types = 1);

namespace Spot\FileManager\Schema;

use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use Neomerx\JsonApi\Schema\BaseSchema;

class DirectoryListingSchema extends BaseSchema
{
    public function getType(): string
    {
        return 'directoryListings';
    }

    public function getId($directoryListing): ?string
    {
        if (!is_array($directoryListing)) {
            throw new \InvalidArgumentException('DirectoryListingSchema can only work on array.');
        }

        return $directoryListing['path'];
    }

    public function getAttributes($directoryListing, ContextInterface $context): iterable
    {
        if (!is_array($directoryListing)) {
            throw new \InvalidArgumentException('DirectoryListingSchema can only work on array.');
        }

        return ['directories' => $directoryListing['directories'], 'files' => $directoryListing['files']];
    }

    public function getRelationships($directoryListing, ContextInterface $context): iterable
    {
        if (!is_array($directoryListing)) {
            throw new \InvalidArgumentException('DirectoryListingSchema can only work on array.');
        }

        return [];
    }
}
