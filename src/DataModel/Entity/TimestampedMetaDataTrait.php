<?php declare(strict_types = 1);

namespace Spot\DataModel\Entity;

trait TimestampedMetaDataTrait
{
    /** @var  \DateTimeInterface */
    private $metaCreatedTimestamp;

    /** @var  \DateTimeInterface */
    private $metaUpdatedTimestamp;

    /** @return  self */
    public function metaDataSetTimestamps(\DateTimeInterface $created, \DateTimeInterface $updated)
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
