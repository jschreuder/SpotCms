<?php declare(strict_types = 1);

namespace Spot\Application\JsonOutput;

interface JsonConverterInterface
{
    /** Returns a type-identification for the output */
    public function getType(): string;

    /** Returns the unique identifier for the output when it's an entity, null otherwise */
    public function getId($page): ?string;

    /** Returns the attributes of the output, except for any related data, must be JSON convertable */
    public function getAttributes($page): array;

    /** Returns the related data for the output, must be JSON convertable */
    public function getRelationships($page): array;
}
