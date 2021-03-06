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

    /** @var  UuidInterface */
    private $fileUuid;

    /** @var  FileNameValue */
    private $name;

    /** @var  FilePathValue */
    private $path;

    /** @var  MimeTypeValue */
    private $mimeType;

    /** @var  resource */
    private $stream;

    public function __construct(
        UuidInterface $fileUuid,
        FileNameValue $name,
        FilePathValue $path,
        MimeTypeValue $mimeType,
        $stream
    ) {
        $this->fileUuid = $fileUuid;
        $this
            ->setName($name)
            ->setPath($path)
            ->setMimeType($mimeType)
            ->setStream($stream);
    }

    public function getUuid() : UuidInterface
    {
        return $this->fileUuid;
    }

    public function getName() : FileNameValue
    {
        return $this->name;
    }

    public function setName(FileNameValue $name) : File
    {
        $this->name = $name;
        return $this;
    }

    public function getPath() : FilePathValue
    {
        return $this->path;
    }

    public function setPath(FilePathValue $path) : File
    {
        $this->path = $path;
        return $this;
    }

    public function getMimeType() : MimeTypeValue
    {
        return $this->mimeType;
    }

    public function setMimeType(MimeTypeValue $mimeType) : File
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /** @return  resource */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @param  resource $stream
     */
    public function setStream($stream) : File
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Invalid Stream given to File entity.');
        }

        $this->stream = $stream;
        return $this;
    }
}
