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
     * @param  \PDOStatement $uniqueStatement
     * @param  \PDOStatement $insertStatement
     */
    public function it_canCreateANewFileWithAUniqueName($uniqueStatement, $insertStatement)
    {
        $uuid = Uuid::uuid4();
        $name = FileNameValue::get('file.name');
        $newName = FileNameValue::get('file_4.name');
        $path = FilePathValue::get('/uploads/');
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

    /**
     * @param  \Spot\FileManager\Entity\File $file
     */
    public function it_rollsBackAfterException($file)
    {
        $uuid = Uuid::uuid4();
        $stream = tmpfile();
        $file->getUuid()->willReturn($uuid);
        $file->getStream()->willReturn($stream);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->objectRepository->create(File::TYPE, $uuid)
            ->shouldBeCalled();

        $this->fileSystem->writeStream($uuid->toString(), $stream)
            ->willReturn(false);

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
     * @param  \PDOStatement $updateStatement
     */
    public function it_willRollBackAfterFailedUpdateFileMetaData($file, $updateStatement)
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
            ->willThrow(new \RuntimeException());

        $this->objectRepository->update(File::TYPE, $uuid)
            ->shouldNotBeCalled();

        $this->pdo->rollBack()
            ->shouldBeCalled();

        $file->metaDataSetUpdateTimestamp(new Argument\Token\TypeToken(\DateTimeImmutable::class))
            ->shouldNotBeCalled();

        $this->shouldThrow(\RuntimeException::class)->duringUpdateMetaData($file);
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
        $path = '/sg-1/season1/';
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
        $path = '/sg-1/season1/';
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
        $path = '/sg-1/season1/';

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM files'))
            ->willReturn($statement);
        $statement->execute(['full_path' => $path . $name])->shouldBeCalled();
        $statement->rowCount()->willReturn(0);

        $this->shouldThrow(NoUniqueResultException::class)->duringGetByFullPath($path . $name);
    }

    /**
     * @param  \PDOStatement $statement
     */
    public function it_canGetMultipleFilesInPath($statement)
    {
        $path = '/sg-1/season1/';
        $uuid1 = Uuid::uuid4();
        $name1 = 'the-broca-divide.ep';
        $mime1 = 'stargate/sg-1';
        $stream1 = tmpfile();
        $uuid2 = Uuid::uuid4();
        $name2 = 'the-first-commandment.ep';
        $mime2 = 'stargate/sg-1';
        $stream2 = tmpfile();
        $uuid3 = Uuid::uuid4();
        $name3 = 'cold-lazarus.ep';
        $mime3 = 'stargate/sg-1';
        $stream3 = tmpfile();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM files'))
            ->willReturn($statement);
        $statement->execute(['path' => $path])->shouldBeCalled();
        $statement->fetch(\PDO::FETCH_ASSOC)->willReturn(
            [
                'file_uuid' => $uuid1->getBytes(),
                'name' => $name1,
                'path' => $path,
                'mime_type' => $mime1,
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ],
            [
                'file_uuid' => $uuid2->getBytes(),
                'name' => $name2,
                'path' => $path,
                'mime_type' => $mime2,
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ],
            [
                'file_uuid' => $uuid3->getBytes(),
                'name' => $name3,
                'path' => $path,
                'mime_type' => $mime3,
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ],
            false
        );

        $this->fileSystem->readStream($uuid1->toString())
            ->willReturn($stream1);
        $this->fileSystem->readStream($uuid2->toString())
            ->willReturn($stream2);
        $this->fileSystem->readStream($uuid3->toString())
            ->willReturn($stream3);

        $files = $this->getFilesInPath($path);

        $files[0]->shouldBeAnInstanceOf(File::class);
        $files[0]->getName()->toString()->shouldReturn($name1);
        $files[0]->getPath()->toString()->shouldReturn($path);
        $files[0]->getMimeType()->toString()->shouldReturn($mime1);
        $files[0]->getStream()->shouldReturn($stream1);
        $files[1]->shouldBeAnInstanceOf(File::class);
        $files[1]->getName()->toString()->shouldReturn($name2);
        $files[1]->getPath()->toString()->shouldReturn($path);
        $files[1]->getMimeType()->toString()->shouldReturn($mime2);
        $files[1]->getStream()->shouldReturn($stream2);
        $files[2]->shouldBeAnInstanceOf(File::class);
        $files[2]->getName()->toString()->shouldReturn($name3);
        $files[2]->getPath()->toString()->shouldReturn($path);
        $files[2]->getMimeType()->toString()->shouldReturn($mime3);
        $files[2]->getStream()->shouldReturn($stream3);
    }

    /**
     * @param  \PDOStatement $statement
     */
    public function it_canGetNoFilesBackForAPath($statement)
    {
        $path = '/sg-1/season11/';
        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM files'))
            ->willReturn($statement);
        $statement->execute(['path' => $path])->shouldBeCalled();
        $statement->fetch(\PDO::FETCH_ASSOC)->willReturn(false);

        $this->getFilesInPath($path)->shouldHaveCount(0);
    }

    /**
     * @param  \PDOStatement $statement
     */
    public function it_canGetSubdirectoriesInAPath($statement)
    {
        $path = '/sgu/';
        $directories = [
            $path . 'season1/',
            $path . 'season1/minisodes/',
            $path . 'season2/',
            $path . 'season2/minisodes/deleted/',
            $path . 'specials/minisodes/',
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
