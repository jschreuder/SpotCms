<?php declare(strict_types = 1);

namespace Spot\FileManager\Repository;

use League\Flysystem\FilesystemInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\FileManager\Entity\File;
use Spot\FileManager\Value\FileNameValue;
use Spot\FileManager\Value\FilePathValue;
use Spot\FileManager\Value\MimeTypeValue;

class FileRepository
{
    /** @var  FilesystemInterface */
    private $fileSystem;

    /** @var  \PDO */
    private $pdo;

    /** @var  ObjectRepository */
    private $objectRepository;

    public function __construct(FilesystemInterface $fileSystem, \PDO $pdo, ObjectRepository $objectRepository)
    {
        $this->fileSystem = $fileSystem;
        $this->pdo = $pdo;
        $this->objectRepository = $objectRepository;
    }

    public function createFromUpload(File $file)
    {
        $this->pdo->beginTransaction();
        try {
            $this->objectRepository->create(File::TYPE, $file->getUuid());

            $file->setName($this->getUniqueFileName($file->getPath(), $file->getName()));
            if (!$this->fileSystem->writeStream($file->getUuid()->toString(), $file->getStream())) {
                throw new \RuntimeException('Failed to process uploaded file.');
            }
            $file->setStream($this->fileSystem->readStream($file->getUuid()->toString()));

            $this->pdo->prepare('
                INSERT INTO files (file_uuid, name, path, mime_type)
                     VALUES (:file_uuid, :name, :path, :mime_type)
            ')->execute([
                'file_uuid' => $file->getUuid()->getBytes(),
                'name' => $file->getName()->toString(),
                'path' => $file->getPath()->toString(),
                'mime_type' => $file->getMimeType()->toString(),
            ]);
            $this->pdo->commit();
            $file->metaDataSetInsertTimestamp(new \DateTimeImmutable());
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            if ($this->fileSystem->has($file->getUuid()->toString())) {
                $this->fileSystem->delete($file->getUuid()->toString());
            }
            throw $exception;
        }
    }

    public function updateContent(File $file)
    {
        if (!$this->fileSystem->putStream($file->getUuid()->toString(), $file->getStream())) {
            throw new \RuntimeException('Failed to update file content.');
        }
        $this->objectRepository->update(File::TYPE, $file->getUuid());
    }

    public function updateMetaData(File $file)
    {
        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare('
                UPDATE files
                   SET name = :name,
                       path = :path,
                       mime_type = :mime_type
                 WHERE file_uuid = :file_uuid
            ');
            $query->execute([
                'file_uuid' => $file->getUuid()->getBytes(),
                'name' => $file->getName()->toString(),
                'path' => $file->getPath()->toString(),
                'mime_type' => $file->getMimeType()->toString(),
            ]);

            // When at least one of the fields changes, the rowCount will be 1 and an update occurred
            if ($query->rowCount() === 1) {
                $this->objectRepository->update(File::TYPE, $file->getUuid());
            }

            $file->metaDataSetUpdateTimestamp(new \DateTimeImmutable());
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function delete(File $file)
    {
        $this->pdo->beginTransaction();
        try {
            if (!$this->fileSystem->delete($file->getUuid()->toString())) {
                throw new \RuntimeException('Failed to delete file');
            }
            $this->objectRepository->delete(File::TYPE, $file->getUuid());
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function getFileFromRow(array $row) : File
    {
        $uuid = Uuid::fromBytes($row['file_uuid']);
        return (new File(
                $uuid,
                FileNameValue::get($row['name']),
                FilePathValue::get($row['path']),
                MimeTypeValue::get($row['mime_type']),
                $this->fileSystem->readStream($uuid->toString())
            ))
            ->metaDataSetInsertTimestamp(new \DateTimeImmutable($row['created']))
            ->metaDataSetUpdateTimestamp(new \DateTimeImmutable($row['updated']));
    }

    public function getByUuid(UuidInterface $uuid) : File
    {
        $query = $this->pdo->prepare('
                SELECT file_uuid, name, path, mime_type, created, updated
                  FROM files
            INNER JOIN objects ON (file_uuid = uuid AND type = "files")
                 WHERE file_uuid = :file_uuid
        ');
        $query->execute(['file_uuid' => $uuid->getBytes()]);

        if ($query->rowCount() !== 1) {
            throw new NoUniqueResultException('Expected a unique result, but got ' . $query->rowCount() . ' results.');
        }

        return $this->getFileFromRow($query->fetch(\PDO::FETCH_ASSOC));
    }

    public function getByFullPath(string $path) : File
    {
        $query = $this->pdo->prepare('
                SELECT file_uuid, name, path, mime_type, created, updated
                  FROM files
            INNER JOIN objects ON (file_uuid = uuid AND type = "files")
                 WHERE CONCAT(path, "/", name) = :full_path
        ');
        $query->execute(['full_path' => $path]);

        if ($query->rowCount() !== 1) {
            throw new NoUniqueResultException('Expected a unique result, but got ' . $query->rowCount() . ' results.');
        }

        return $this->getFileFromRow($query->fetch(\PDO::FETCH_ASSOC));
    }

    /** @return  File[] */
    public function getFilesInPath(string $path) : array
    {
        $query = $this->pdo->prepare('
                SELECT file_uuid, name, path, mime_type, created, updated
                  FROM files
            INNER JOIN objects ON (file_uuid = uuid AND type = "files")
                 WHERE path = :path
              ORDER BY name ASC
        ');
        $query->execute(['path' => $path]);

        $files = [];
        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $files[] = $this->getFileFromRow($row);
        }
        return $files;
    }

    /** @return  string[] */
    public function getDirectoriesInPath(string $path) : array
    {
        $query = $this->pdo->prepare('
              SELECT path
                FROM files
               WHERE path LIKE CONCAT(:path, "%") AND path != :path
            GROUP BY path
            ORDER BY path ASC
        ');
        $query->execute(['path' => $path]);

        $directories = [];
        while ($directory = $query->fetchColumn()) {
            if (count($directories) > 0 && strpos($directory, end($directories)->toString()) === 0) {
                continue;
            }
            $directories[] = FilePathValue::get($directory);
        }
        return $directories;
    }

    private function getUniqueFileName(FilePathValue $path, FileNameValue $name) : FileNameValue
    {
        $nameInfo = pathinfo($name->toString());
        $nameRegex = preg_quote($nameInfo['filename']) . '(_[0-9]+)?\.' . preg_quote($nameInfo['extension'] ?? '');

        $query = $this->pdo->prepare('
              SELECT name
                FROM files
               WHERE path = :path AND name REGEXP :name
            ORDER BY name ASC
        ');
        $query->execute(['path' => $path->toString(), 'name' => $nameRegex]);

        if ($query->rowCount() === 0) {
            return $name;
        }

        $max = 0;
        while ($row = $query->fetchColumn()) {
            if ($row === $name->toString()) {
                continue;
            }
            preg_match('#' . $nameRegex . '#', $row, $match);
            $number = intval(substr($match[1], 1));
            $max = max($max, $number);
        }
        return FileNameValue::get(
            $nameInfo['filename'] . '_' . strval($max + 1) . '.' . ($nameInfo['extension'] ?? '')
        );
    }
}
