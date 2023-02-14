<?php declare(strict_types = 1);

namespace Spot\FileManager\Entity;

use Ramsey\Uuid\UuidInterface;
use Spot\DataModel\Entity\TimestampedMetaDataTrait;
use Spot\FileManager\Value\FileNameValue;
use Spot\FileManager\Value\FilePathValue;
use Spot\FileManager\Value\MimeTypeValue;

class File
{
    use TimestampedMetaDataTrait;

    const TYPE = 'files';

    /** @var  resource */
    private $stream;

    public function __construct(
        private UuidInterface $fileUuid,
        private FileNameValue $name,
        private FilePathValue $path,
        private MimeTypeValue $mimeType,
        $stream
    )
    {
        $this->setStream($stream);
    }

    public function getUuid(): UuidInterface
    {
        return $this->fileUuid;
    }

    public function getName(): FileNameValue
    {
        return $this->name;
    }

    public function setName(FileNameValue $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPath(): FilePathValue
    {
        return $this->path;
    }

    public function setPath(FilePathValue $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getMimeType(): MimeTypeValue
    {
        return $this->mimeType;
    }

    public function setMimeType(MimeTypeValue $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /** @return  resource */
    public function getStream()
    {
        return $this->stream;
    }

    /** @param  resource $stream */
    public function setStream($stream): self
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Invalid Stream given to File entity.');
        }

        $this->stream = $stream;
        return $this;
    }
}
