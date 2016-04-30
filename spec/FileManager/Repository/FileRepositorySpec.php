<?php

namespace spec\Spot\FileManager\Repository;

use League\Flysystem\FilesystemInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\FileManager\Entity\File;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Value\FileNameValue;
use Spot\FileManager\Value\FilePathValue;
use Spot\FileManager\Value\MimeTypeValue;

/** @mixin  FileRepository */
class FileRepositorySpec extends ObjectBehavior
{
    /** @var  FilesystemInterface */
    private $fileSystem;

    /** @var  \PDO */
    private $pdo;

    /** @var  ObjectRepository */
    private $objectRepository;

    public function let(FilesystemInterface $fileSystem, \PDO $pdo, ObjectRepository $objectRepository)
    {
        $this->fileSystem = $fileSystem;
        $this->pdo = $pdo;
        $this->objectRepository = $objectRepository;
        $this->beConstructedWith($fileSystem, $pdo, $objectRepository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FileRepository::class);
    }

    public function it_can_create_a_new_file(File $file, \PDOStatement $uniqueStatement, \PDOStatement $insertStatement)
    {
        $uuid = Uuid::uuid4();
        $name = FileNameValue::get('file.name');
        $path = FilePathValue::get('/uploads');
        $mime = MimeTypeValue::get('text/xml');
        $stream = tmpfile();
        $stream2 = tmpfile();

        $file->getUuid()->willReturn($uuid);
        $file->getName()->willReturn($name);
        $file->setName($name)->willReturn($file);
        $file->getPath()->willReturn($path);
        $file->getMimeType()->willReturn($mime);
        $file->getStream()->willReturn($stream);
        $file->setStream($stream2)->willReturn($file);

        $this->fileSystem->writeStream($uuid->toString(), $stream)
            ->willReturn(true);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->objectRepository->create(File::TYPE, $uuid)
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('name REGEXP :name'))
            ->willReturn($uniqueStatement);
        $uniqueStatement->execute(['path' => $path->toString(), 'name' => 'file(_[0-9]+)?\.name'])
            ->shouldBeCalled();
        $uniqueStatement->rowCount()
            ->willReturn(0);

        $this->fileSystem->readStream($uuid->toString())
            ->willReturn($stream2);

        $this->pdo->prepare(new Argument\Token\StringContainsToken('INSERT INTO files'))
            ->willReturn($insertStatement);
        $insertStatement->execute([
            'file_uuid' => $uuid->getBytes(),
            'name' => $name->toString(),
            'path' => $path->toString(),
            'mime_type' => $mime->toString(),
        ]);

        $this->pdo->commit()
            ->shouldBeCalled();

        $file->metaDataSetInsertTimestamp(new Argument\Token\TypeToken(\DateTimeImmutable::class))
            ->willReturn($file);

        $this->createFromUpload($file);

        // cleanup
        fclose($stream);
        fclose($stream2);
    }

    public function it_can_create_a_new_file_with_a_unique_name(
        \PDOStatement $uniqueStatement,
        \PDOStatement $insertStatement
    )
    {
        $uuid = Uuid::uuid4();
        $name = FileNameValue::get('file.name');
        $newName = FileNameValue::get('file_4.name');
        $path = FilePathValue::get('/uploads');
        $mime = MimeTypeValue::get('text/xml');
        $stream = tmpfile();
        $stream2 = tmpfile();

        $file = new File($uuid, $name, $path, $mime, $stream);

        $this->fileSystem->writeStream($uuid->toString(), $stream)
            ->willReturn(true);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->objectRepository->create(File::TYPE, $uuid)
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('name REGEXP :name'))
            ->willReturn($uniqueStatement);
        $uniqueStatement->execute(['path' => $path->toString(), 'name' => 'file(_[0-9]+)?\.name'])
            ->shouldBeCalled();
        $uniqueStatement->rowCount()
            ->willReturn(3);
        $uniqueStatement->fetchColumn()
            ->willReturn(
                'file.name',
                'file_2.name',
                'file_3.name',
                false
            );

        $this->fileSystem->readStream($uuid->toString())
            ->willReturn($stream2);

        $this->pdo->prepare(new Argument\Token\StringContainsToken('INSERT INTO files'))
            ->willReturn($insertStatement);
        $insertStatement->execute([
            'file_uuid' => $uuid->getBytes(),
            'name' => $newName->toString(),
            'path' => $path->toString(),
            'mime_type' => $mime->toString(),
        ]);

        $this->pdo->commit()
            ->shouldBeCalled();

        $this->createFromUpload($file);

        // cleanup
        fclose($stream);
        fclose($stream2);
    }

    public function it_rolls_back_after_exception(File $file, \PDOStatement $uniqueStatement)
    {
        $uuid = Uuid::uuid4();
        $name = FileNameValue::get('file.name');
        $path = FilePathValue::get('/uploads');
        $stream = tmpfile();

        $file->getUuid()->willReturn($uuid);
        $file->getName()->willReturn($name);
        $file->setName($name)->willReturn($file);
        $file->getPath()->willReturn($path);
        $file->getStream()->willReturn($stream);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->objectRepository->create(File::TYPE, $uuid)
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('name REGEXP :name'))
            ->willReturn($uniqueStatement);
        $uniqueStatement->execute(['path' => $path->toString(), 'name' => 'file(_[0-9]+)?\.name'])
            ->shouldBeCalled();
        $uniqueStatement->rowCount()
            ->willReturn(0);

        $this->fileSystem->writeStream($uuid->toString(), $stream)
            ->willReturn(false);

        $this->pdo->rollBack()
            ->shouldBeCalled();
        $this->fileSystem->has($uuid->toString())
            ->willReturn(false);

        $this->shouldThrow(\RuntimeException::class)->duringCreateFromUpload($file);
    }

    public function it_can_also_delete_after_exception(File $file)
    {
        $uuid = Uuid::uuid4();
        $file->getUuid()->willReturn($uuid);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->objectRepository->create(File::TYPE, $uuid)
            ->willThrow(new \RuntimeException());

        $this->pdo->rollBack()
            ->shouldBeCalled();
        $this->fileSystem->has($uuid->toString())
            ->willReturn(true);
        $this->fileSystem->delete($uuid->toString())
            ->willReturn(true);

        $this->shouldThrow(\RuntimeException::class)->duringCreateFromUpload($file);
    }

    public function it_can_update_a_files_content(File $file)
    {
        $uuid = Uuid::uuid4();
        $stream = tmpfile();
        $file->getUuid()->willReturn($uuid);
        $file->getStream()->willReturn($stream);

        $this->fileSystem->putStream($uuid->toString(), $stream)
            ->willReturn(true);
        $this->objectRepository->update(File::TYPE, $uuid)
            ->shouldBeCalled();

        $this->updateContent($file);

        // cleanup
        fclose($stream);
    }

    public function it_can_errors_on_upload_failure_with_update_file_content(File $file)
    {
        $uuid = Uuid::uuid4();
        $stream = tmpfile();
        $file->getUuid()->willReturn($uuid);
        $file->getStream()->willReturn($stream);

        $this->fileSystem->putStream($uuid->toString(), $stream)
            ->willReturn(false);
        $this->objectRepository->update(File::TYPE, $uuid)
            ->shouldNotBeCalled();

        $this->shouldThrow(\RuntimeException::class)->duringUpdateContent($file);

        // cleanup
        fclose($stream);
    }

    public function it_can_update_file_meta_data(File $file, \PDOStatement $updateStatement)
    {
        $uuid = Uuid::uuid4();
        $name = FileNameValue::get('file.name');
        $path = FilePathValue::get('/uploads');
        $mime = MimeTypeValue::get('text/xml');

        $file->getUuid()->willReturn($uuid);
        $file->getName()->willReturn($name);
        $file->setName($name)->willReturn($file);
        $file->getPath()->willReturn($path);
        $file->getMimeType()->willReturn($mime);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('UPDATE files'))
            ->willReturn($updateStatement);
        $updateStatement->execute([
            'file_uuid' => $uuid->getBytes(),
            'name' => $name->toString(),
            'path' => $path->toString(),
            'mime_type' => $mime->toString(),
            ])
            ->shouldBeCalled();
        $updateStatement->rowCount()
            ->willReturn(1);

        $this->objectRepository->update(File::TYPE, $uuid)
            ->shouldBeCalled();

        $this->pdo->commit()
            ->shouldBeCalled();

        $file->metaDataSetUpdateTimestamp(new Argument\Token\TypeToken(\DateTimeImmutable::class))
            ->willReturn($file);

        $this->updateMetaData($file);
    }

    public function it_can_update_file_meta_data_without_actual_changes(File $file, \PDOStatement $updateStatement)
    {
        $uuid = Uuid::uuid4();
        $name = FileNameValue::get('file.name');
        $path = FilePathValue::get('/uploads');
        $mime = MimeTypeValue::get('text/xml');

        $file->getUuid()->willReturn($uuid);
        $file->getName()->willReturn($name);
        $file->setName($name)->willReturn($file);
        $file->getPath()->willReturn($path);
        $file->getMimeType()->willReturn($mime);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('UPDATE files'))
            ->willReturn($updateStatement);
        $updateStatement->execute([
            'file_uuid' => $uuid->getBytes(),
            'name' => $name->toString(),
            'path' => $path->toString(),
            'mime_type' => $mime->toString(),
        ])
            ->shouldBeCalled();
        $updateStatement->rowCount()
            ->willReturn(0);

        $this->objectRepository->update(File::TYPE, $uuid)
            ->shouldNotBeCalled();

        $this->pdo->commit()
            ->shouldBeCalled();

        $file->metaDataSetUpdateTimestamp(new Argument\Token\TypeToken(\DateTimeImmutable::class))
            ->willReturn($file);

        $this->updateMetaData($file);
    }

    public function it_will_roll_back_after_failed_update_file_meta_data(File $file)
    {
        $uuid = Uuid::uuid4();
        $name = FileNameValue::get('file.name');
        $path = FilePathValue::get('/uploads');
        $mime = MimeTypeValue::get('text/xml');

        $file->getUuid()->willReturn($uuid);
        $file->getName()->willReturn($name);
        $file->setName($name)->willReturn($file);
        $file->getPath()->willReturn($path);
        $file->getMimeType()->willReturn($mime);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('UPDATE files'))
            ->willThrow(new \RuntimeException());

        $this->objectRepository->update(File::TYPE, $uuid)
            ->shouldNotBeCalled();

        $this->pdo->rollBack()
            ->shouldBeCalled();

        $file->metaDataSetUpdateTimestamp(new Argument\Token\TypeToken(\DateTimeImmutable::class))
            ->shouldNotBeCalled();

        $this->shouldThrow(\RuntimeException::class)->duringUpdateMetaData($file);
    }

    public function it_can_delete_a_file(File $file)
    {
        $uuid = Uuid::uuid4();
        $file->getUuid()->willReturn($uuid);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->fileSystem->delete($uuid->toString())
            ->willReturn(true);
        $this->objectRepository->delete(File::TYPE, $uuid)
            ->shouldBeCalled();
        $this->pdo->commit()
            ->shouldBeCalled();

        $this->delete($file);
    }

    public function it_can_will_error_and_roll_back_when_delete_fails(File $file)
    {
        $uuid = Uuid::uuid4();
        $file->getUuid()->willReturn($uuid);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->fileSystem->delete($uuid->toString())
            ->willReturn(false);
        $this->objectRepository->delete(File::TYPE, $uuid)
            ->shouldNotBeCalled();
        $this->pdo->rollBack()
            ->shouldBeCalled();

        $this->shouldThrow(\RuntimeException::class)->duringDelete($file);
    }

    public function it_can_fetch_a_file_by_its_uuid(\PDOStatement $statement)
    {
        $uuid = Uuid::uuid4();
        $name = 'children-of-the-gods.ep';
        $path = '/sg-1/season1';
        $mime = 'stargate/sg-1';
        $stream = tmpfile();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM files'))
            ->willReturn($statement);
        $statement->execute(['file_uuid' => $uuid->getBytes()])->shouldBeCalled();
        $statement->rowCount()->willReturn(1);
        $statement->fetch(\PDO::FETCH_ASSOC)->willReturn([
            'file_uuid' => $uuid->getBytes(),
            'name' => $name,
            'path' => $path,
            'mime_type' => $mime,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->fileSystem->readStream($uuid->toString())
            ->willReturn($stream);

        $file = $this->getByUuid($uuid);
        $file->shouldBeAnInstanceOf(File::class);
        $file->getName()->toString()->shouldReturn($name);
        $file->getPath()->toString()->shouldReturn($path);
        $file->getMimeType()->toString()->shouldReturn($mime);
        $file->getStream()->shouldReturn($stream);
    }

    public function it_errors_without_result_when_fetching_file_by_its_uuid(\PDOStatement $statement)
    {
        $uuid = Uuid::uuid4();
        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM files'))
            ->willReturn($statement);
        $statement->execute(['file_uuid' => $uuid->getBytes()])->shouldBeCalled();
        $statement->rowCount()->willReturn(0);

        $this->shouldThrow(NoUniqueResultException::class)->duringGetByUuid($uuid);
    }

    public function it_can_fetch_a_file_by_its_full_path(\PDOStatement $statement)
    {
        $uuid = Uuid::uuid4();
        $name = 'the-enemy-within.ep';
        $path = '/sg-1/season1';
        $mime = 'stargate/sg-1';
        $stream = tmpfile();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM files'))
            ->willReturn($statement);
        $statement->execute(['full_path' => $path . $name])->shouldBeCalled();
        $statement->rowCount()->willReturn(1);
        $statement->fetch(\PDO::FETCH_ASSOC)->willReturn([
            'file_uuid' => $uuid->getBytes(),
            'name' => $name,
            'path' => $path,
            'mime_type' => $mime,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $this->fileSystem->readStream($uuid->toString())
            ->willReturn($stream);

        $file = $this->getByFullPath($path . $name);
        $file->shouldBeAnInstanceOf(File::class);
        $file->getName()->toString()->shouldReturn($name);
        $file->getPath()->toString()->shouldReturn($path);
        $file->getMimeType()->toString()->shouldReturn($mime);
        $file->getStream()->shouldReturn($stream);
    }

    public function it_errors_without_result_when_fetching_file_by_its_full_path(\PDOStatement $statement)
    {
        $name = 'emancipation.ep';
        $path = '/sg-1/season1';

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM files'))
            ->willReturn($statement);
        $statement->execute(['full_path' => $path . $name])->shouldBeCalled();
        $statement->rowCount()->willReturn(0);

        $this->shouldThrow(NoUniqueResultException::class)->duringGetByFullPath($path . $name);
    }

    public function it_can_get_multiple_files_in_path(\PDOStatement $statement)
    {
        $path = '/sg-1/season1';
        $name1 = 'the-broca-divide.ep';
        $name2 = 'the-first-commandment.ep';
        $name3 = 'cold-lazarus.ep';

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM files'))
            ->willReturn($statement);
        $statement->execute(['path' => $path])->shouldBeCalled();
        $statement->fetch(\PDO::FETCH_ASSOC)->willReturn(
            [
                'name' => $name1,
            ],
            [
                'name' => $name2,
            ],
            [
                'name' => $name3,
            ],
            false
        );

        $files = $this->getFileNamesInPath($path);

        $files[0]->shouldBe($name1);
        $files[1]->shouldBe($name2);
        $files[2]->shouldBe($name3);
    }

    public function it_can_get_no_files_back_for_a_path(\PDOStatement $statement)
    {
        $path = '/sg-1/season11';
        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM files'))
            ->willReturn($statement);
        $statement->execute(['path' => $path])->shouldBeCalled();
        $statement->fetch(\PDO::FETCH_ASSOC)->willReturn(false);

        $this->getFileNamesInPath($path)->shouldHaveCount(0);
    }

    public function it_can_get_subdirectories_in_a_path(\PDOStatement $statement)
    {
        $path = '/sgu';
        $directories = [
            $path . '/season1',
            $path . '/season1/minisodes',
            $path . '/season2',
            $path . '/season2/minisodes/deleted',
            $path . '/specials/minisodes',
        ];

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM files'))
            ->willReturn($statement);
        $statement->execute(['path' => $path])->shouldBeCalled();
        $statement->fetchColumn()->willReturn(
            $directories[0],
            $directories[1],
            $directories[2],
            $directories[3],
            $directories[4],
            false
        );

        $result = $this->getDirectoriesInPath($path);
        $result[0]->toString()->shouldReturn($directories[0]);
        $result[1]->toString()->shouldReturn($directories[2]);
        $result[2]->toString()->shouldReturn($directories[4]);
    }
}
