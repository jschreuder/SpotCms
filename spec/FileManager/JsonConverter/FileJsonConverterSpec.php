<?php

namespace spec\Spot\FileManager\JsonConverter;

use PhpSpec\ObjectBehavior;
use Ramsey\Uuid\Uuid;
use Spot\FileManager\Entity\File;
use Spot\FileManager\JsonConverter\FileJsonConverter;
use Spot\FileManager\Value\FileNameValue;
use Spot\FileManager\Value\FilePathValue;
use Spot\FileManager\Value\MimeTypeValue;

/** @mixin  FileJsonConverter */
class FileJsonConverterSpec extends ObjectBehavior
{
    /** @var  File */
    private $file;

    public function let()
    {
        $this->file = (new File(
                Uuid::uuid4(),
                FileNameValue::get('file.ext'),
                FilePathValue::get('/path/to'),
                MimeTypeValue::get('text/text'),
                tmpfile()
            ))
            ->metaDataSetInsertTimestamp(new \DateTimeImmutable())
            ->metaDataSetUpdateTimestamp(new \DateTimeImmutable());
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FileJsonConverter::class);
    }

    public function it_can_give_its_entity_type()
    {
        $this->getType()->shouldReturn(File::TYPE);
    }

    public function it_can_give_an_entities_id()
    {
        $this->getId($this->file)->shouldReturn($this->file->getUuid()->toString());
    }

    public function it_errors_when_get_id_given_non_page_entity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetId(new \stdClass());
    }

    public function it_can_transform_file_to_array()
    {
        $attributes = $this->getAttributes($this->file);
        $attributes['name']->shouldBe($this->file->getName()->toString());
        $attributes['path']->shouldBe($this->file->getPath()->toString());
        $attributes['mime_type']->shouldBe($this->file->getMimeType()->toString());
        $attributes['meta']->shouldBe([
            'created' => $this->file->metaDataGetCreatedTimestamp()->format('c'),
            'updated' => $this->file->metaDataGetUpdatedTimestamp()->format('c'),
        ]);
    }

    public function it_errors_when_get_attributes_given_non_file_entity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetAttributes(new \stdClass());
    }

    public function it_has_no_relationships()
    {
        $this->getRelationships($this->file)->shouldReturn([]);
    }

    public function it_errors_when_get_relationship_given_non_page_entity()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGetRelationships(new \stdClass());
    }
}
