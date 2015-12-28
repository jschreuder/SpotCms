<?php declare(strict_types = 1);

namespace Spot\FileManager\Entity;

use Ramsey\Uuid\UuidInterface;
use Spot\DataModel\Entity\TimestampedMetaDataTrait;

class File
{
    use TimestampedMetaDataTrait;

    const TYPE = 'files';

    /** @var  UuidInterface */
    private $fileUuid;

    /** @var  string */
    private $name;

    /** @var  string */
    private $path;

    /** @var  string */
    private $mimeType;

    public function __construct(UuidInterface $fileUuid, string $name, string $path, string $mimeType)
    {
        $this->fileUuid = $fileUuid;
        $this
            ->setName($name)
            ->setPath($path)
            ->setMimeType($mimeType);
    }

    public function getUuid() : UuidInterface
    {
        return $this->fileUuid;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : File
    {
        $this->name = $name;
        return $this;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function setPath(string $path) : File
    {
        $this->path = $path;
        return $this;
    }

    public function getMimeType() : string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType) : File
    {
        $this->mimeType = $mimeType;
        return $this;
    }
}
