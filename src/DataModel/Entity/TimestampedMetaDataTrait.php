<?php declare(strict_types = 1);

namespace Spot\DataModel\Entity;

trait TimestampedMetaDataTrait
{
    /** @var  \DateTimeInterface */
    private $metaCreatedTimestamp;

    /** @var  \DateTimeInterface */
    private $metaUpdatedTimestamp;

    /** @return  self */
    public function metaDataSetInsertTimestamp(\DateTimeInterface $created)
    {
        $this->metaCreatedTimestamp = $created;
        $this->metaUpdatedTimestamp = $created;
        return $this;
    }

    /** @return  self */
    public function metaDataSetUpdateTimestamp(\DateTimeInterface $updated)
    {
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
