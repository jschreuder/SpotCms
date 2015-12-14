<?php declare(strict_types = 1);

namespace Spot\DataModel\Entity;

trait ObjectMetaDataTrait
{
    /** @var  \DateTimeInterface */
    private $metaCreated;

    /** @var  \DateTimeInterface */
    private $metaUpdated;

    public function metaDataSet(\DateTimeInterface $created, \DateTimeInterface $updated) : self
    {
        $this->metaCreated = $created;
        $this->metaUpdated = $updated;
        return $this;
    }

    public function metaDataGetCreated() : \DateTimeInterface
    {
        return $this->metaCreated;
    }

    public function metaDataGetUpdated() : \DateTimeInterface
    {
        return $this->metaUpdated;
    }
}
