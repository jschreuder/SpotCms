<?php declare(strict_types = 1);

namespace Spot\DataModel\Entity;

use DateTimeInterface;

trait TimestampedMetaDataTrait
{
    private DateTimeInterface $metaCreatedTimestamp;
    private DateTimeInterface $metaUpdatedTimestamp;

    public function metaDataSetInsertTimestamp(\DateTimeInterface $created): self
    {
        $this->metaCreatedTimestamp = $created;
        $this->metaUpdatedTimestamp = $created;
        return $this;
    }

    public function metaDataSetUpdateTimestamp(\DateTimeInterface $updated): self
    {
        $this->metaUpdatedTimestamp = $updated;
        return $this;
    }

    public function metaDataGetCreatedTimestamp(): DateTimeInterface
    {
        return $this->metaCreatedTimestamp;
    }

    public function metaDataGetUpdatedTimestamp(): DateTimeInterface
    {
        return $this->metaUpdatedTimestamp;
    }
}
