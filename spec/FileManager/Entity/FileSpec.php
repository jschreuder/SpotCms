<?php

namespace spec\Spot\FileManager\Entity;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\FileManager\Entity\File;
use Spot\FileManager\Value\FileNameValue;
use Spot\FileManager\Value\FilePathValue;
use Spot\FileManager\Value\MimeTypeValue;

/** @mixin  File */
class FileSpec extends ObjectBehavior
{
    /** @var  UuidInterface */
    private $uuid;

    /** @var  FileNameValue */
    private $name;

    /** @var  FilePathValue */
    private $path;

    /** @var  MimeTypeValue */
    private $mimeType;

    /** @var  resource */
    private $stream;

    public function let()
    {
        $this->uuid = Uuid::uuid4();
        $this->name = FileNameValue::get('test.txt');
        $this->path = FilePathValue::get('/path/to/file/');
        $this->mimeType = MimeTypeValue::get('text/text');
        $this->stream = tmpfile();
        $this->beConstructedWith($this->uuid, $this->name, $this->path, $this->mimeType, $this->stream);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(File::class);
    }

    public function it_canGiveItsUuid()
    {
        $this->getUuid()->shouldReturn($this->uuid);
    }

    public function it_canGiveItsName()
    {
        $this->getName()->shouldReturn($this->name);
    }

    public function it_canChangeItsName()
    {
        $newName = FileNameValue::get('other.txt');
        $this->setName($newName)->shouldReturn($this);
        $this->getName()->shouldReturn($newName);
    }

    public function it_canGiveItsPath()
    {
        $this->getPath()->shouldReturn($this->path);
    }

    public function it_canChangeItsPath()
    {
        $newPath = FilePathValue::get('/');
        $this->setPath($newPath)->shouldReturn($this);
        $this->getPath()->shouldReturn($newPath);
    }

    public function it_canGiveItsMimeType()
    {
        $this->getMimeType()->shouldReturn($this->mimeType);
    }

    public function it_canChangeItsMimeType()
    {
        $newMime = MimeTypeValue::get('text/html');
        $this->setMimeType($newMime)->shouldReturn($this);
        $this->getMimeType()->shouldReturn($newMime);
    }

    public function it_canGiveItsStream()
    {
        $this->getStream()->shouldReturn($this->stream);
    }

    public function it_canReplaceItsStream()
    {
        $newStream = tmpfile();
        $this->setStream($newStream)->shouldReturn($this);
        $this->getStream()->shouldReturn($newStream);
    }

    public function it_errorsOnInvalidStream()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringSetStream(null);
    }

    public function it_canSetInsertMetaDataTimestamp()
    {
        $ts = new \DateTimeImmutable();
        $this->metaDataSetInsertTimestamp($ts);
        $this->metaDataGetCreatedTimestamp()->shouldReturn($ts);
        $this->metaDataGetUpdatedTimestamp()->shouldReturn($ts);
    }

    public function it_canSetUpdateMetaDataTimestamp()
    {
        $inserted = new \DateTimeImmutable();
        $updated = new \DateTimeImmutable();
        $this->metaDataSetInsertTimestamp($inserted);
        $this->metaDataSetUpdateTimestamp($updated);
        $this->metaDataGetCreatedTimestamp()->shouldReturn($inserted);
        $this->metaDataGetUpdatedTimestamp()->shouldReturn($updated);
    }
}
