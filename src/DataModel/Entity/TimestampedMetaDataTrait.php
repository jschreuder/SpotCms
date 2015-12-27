<?php declare(strict_types = 1);

namespace Spot\DataModel\Entity;

trait TimestampedMetaDataTrait
{
    /** @var  \DateTimeInterface */
    private $metaCreatedTimestamp;

    /** @var  \DateTimeInterface */
    private $metaUpdatedTimestamp;

    public function metaDataSetTimestamps(\DateTimeInterface $created, \DateTimeInterface $updated) : self
    {
        $this->metaCreatedTimestamp = $created;
        $this->metaUpdatedTimestamp = $updated;
        return $this;
    }

    public function metaDataGetCreatedTimestamp() : \DateTimeInterface
    {
        return $this->metaCreatedTimestamp;
    }

    public function metaDataGetUpdatedTimestamp() : \DateTimeInterface
    {
        return $this->metaUpdatedTimestamp;
    }
}
