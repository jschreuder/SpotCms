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

    /**
     * @param  \League\Flysystem\FilesystemInterface $fileSystem
     * @param  \PDO $pdo
     * @param  \Spot\DataModel\Repository\ObjectRepository $objectRepository
     */
    public function let($fileSystem, $pdo, $objectRepository)
    {
        $this->fileSystem = $fileSystem;
        $this->pdo = $pdo;
        $this->objectRepository = $objectRepository;
        $this->beConstructedWith($fileSystem, $pdo, $objectRepository);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(FileRepository::class);
    }

    /**
     * @param  \Spot\FileManager\Entity\File $file
     * @param  \PDOStatement $uniqueStatement
     * @param  \PDOStatement $insertStatement
     */
    public function it_canCreateANewFile($file, $uniqueStatement, $insertStatement)
    {
        $uuid = Uuid::uuid4();
        $name = FileNameValue::get('file.name');
        $path = FilePathValue::get('/uploads/');
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

    /**
     * @param  \Spot\FileManager\Entity\File $file
     */
    public function it_rollsBackAfterException($file)
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
            ->willReturn(false);

        $this->shouldThrow(\RuntimeException::class)->duringCreateFromUpload($file);
    }

    /**
     * @param  \Spot\FileManager\Entity\File $file
     */
    public function it_canAlsoDeleteAfterException($file)
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

    /**
     * @param  \Spot\FileManager\Entity\File $file
     */
    public function it_canUpdateAFilesContent($file)
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

    /**
     * @param  \Spot\FileManager\Entity\File $file
     */
    public function it_canErrorsOnUploadFailureWithUpdateFileContent($file)
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

    /**
     * @param  \Spot\FileManager\Entity\File $file
     * @param  \PDOStatement $updateStatement
     */
    public function it_canUpdateFileMetaData($file, $updateStatement)
    {
        $uuid = Uuid::uuid4();
        $name = FileNameValue::get('file.name');
        $path = FilePathValue::get('/uploads/');
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

    /**
     * @param  \Spot\FileManager\Entity\File $file
     * @param  \PDOStatement $updateStatement
     */
    public function it_canUpdateFileMetaDataWithoutActualChanges($file, $updateStatement)
    {
        $uuid = Uuid::uuid4();
        $name = FileNameValue::get('file.name');
        $path = FilePathValue::get('/uploads/');
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

    /**
     * @param  \Spot\FileManager\Entity\File $file
     */
    public function it_canDeleteAFile($file)
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

    /**
     * @param  \Spot\FileManager\Entity\File $file
     */
    public function it_canWillErrorAndRollBackWhenDeleteFails($file)
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

    /**
     * @param  \PDOStatement $statement
     */
    public function it_canFetchAFileByItsUuid($statement)
    {
        $uuid = Uuid::uuid4();
        $name = 'children-of-the-gods.ep';
        $path = '/season1/1x01/';
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

    /**
     * @param  \PDOStatement $statement
     */
    public function it_errorsWithoutResultWhenFetchingFileByItsUuid($statement)
    {
        $uuid = Uuid::uuid4();
        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM files'))
            ->willReturn($statement);
        $statement->execute(['file_uuid' => $uuid->getBytes()])->shouldBeCalled();
        $statement->rowCount()->willReturn(0);

        $this->shouldThrow(NoUniqueResultException::class)->duringGetByUuid($uuid);
    }

    /**
     * @param  \PDOStatement $statement
     */
    public function it_canFetchAFileByItsFullPath($statement)
    {
        $uuid = Uuid::uuid4();
        $name = 'the-enemy-within.ep';
        $path = '/season1/1x02/';
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

    /**
     * @param  \PDOStatement $statement
     */
    public function it_errorsWithoutResultWhenFetchingFileByItsFullPath($statement)
    {
        $name = 'emancipation.ep';
        $path = '/season1/1x03/';

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM files'))
            ->willReturn($statement);
        $statement->execute(['full_path' => $path . $name])->shouldBeCalled();
        $statement->rowCount()->willReturn(0);

        $this->shouldThrow(NoUniqueResultException::class)->duringGetByFullPath($path . $name);
    }
}
