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
        $this->path = FilePathValue::get('/path/to/file');
        $this->mimeType = MimeTypeValue::get('text/text');
        $this->stream = tmpfile();
        $this->beConstructedWith($this->uuid, $this->name, $this->path, $this->mimeType, $this->stream);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(File::class);
    }

    public function it_can_give_its_uuid()
    {
        $this->getUuid()->shouldReturn($this->uuid);
    }

    public function it_can_give_its_name()
    {
        $this->getName()->shouldReturn($this->name);
    }

    public function it_can_change_its_name()
    {
        $newName = FileNameValue::get('other.txt');
        $this->setName($newName)->shouldReturn($this);
        $this->getName()->shouldReturn($newName);
    }

    public function it_can_give_its_path()
    {
        $this->getPath()->shouldReturn($this->path);
    }

    public function it_can_change_its_path()
    {
        $newPath = FilePathValue::get('/');
        $this->setPath($newPath)->shouldReturn($this);
        $this->getPath()->shouldReturn($newPath);
    }

    public function it_can_give_its_mime_type()
    {
        $this->getMimeType()->shouldReturn($this->mimeType);
    }

    public function it_can_change_its_mime_type()
    {
        $newMime = MimeTypeValue::get('text/html');
        $this->setMimeType($newMime)->shouldReturn($this);
        $this->getMimeType()->shouldReturn($newMime);
    }

    public function it_can_give_its_stream()
    {
        $this->getStream()->shouldReturn($this->stream);
    }

    public function it_can_replace_its_stream()
    {
        $newStream = tmpfile();
        $this->setStream($newStream)->shouldReturn($this);
        $this->getStream()->shouldReturn($newStream);
    }

    public function it_errors_on_invalid_stream()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringSetStream(null);
    }

    public function it_can_set_insert_meta_data_timestamp()
    {
        $ts = new \DateTimeImmutable();
        $this->metaDataSetInsertTimestamp($ts);
        $this->metaDataGetCreatedTimestamp()->shouldReturn($ts);
        $this->metaDataGetUpdatedTimestamp()->shouldReturn($ts);
    }

    public function it_can_set_update_meta_data_timestamp()
    {
        $inserted = new \DateTimeImmutable();
        $updated = new \DateTimeImmutable();
        $this->metaDataSetInsertTimestamp($inserted);
        $this->metaDataSetUpdateTimestamp($updated);
        $this->metaDataGetCreatedTimestamp()->shouldReturn($inserted);
        $this->metaDataGetUpdatedTimestamp()->shouldReturn($updated);
    }

    public function it_can_recognize_a_non_image()
    {
        $this->beConstructedWith(
            $this->uuid,
            $this->name,
            $this->path,
            MimeTypeValue::get('application/json'),
            $this->stream
        );
        $this->isImage()->shouldReturn(false);
    }

    public function it_can_recognize_an_image()
    {
        $this->beConstructedWith(
            $this->uuid,
            $this->name,
            $this->path,
            MimeTypeValue::get('image/jpg'),
            $this->stream
        );
        $this->isImage()->shouldReturn(true);
    }
}
