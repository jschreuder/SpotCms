<?php declare(strict_types = 1);

namespace Spot\FileManager\Repository;

use League\Flysystem\FilesystemInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\FileManager\Entity\File;

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

    public function createFromUpload(File $file, string $uploadPath)
    {
        $this->pdo->beginTransaction();
        try {
            $this->objectRepository->create(File::TYPE, $file->getUuid());
            $this->fileSystem->putStream($file->getUuid()->toString(), fopen($uploadPath, 'r'));
            $this->pdo->prepare('
                INSERT INTO files (file_uuid, name, path, mime_type)
                     VALUES (:file_uuid, :name, :path, :mime_type)
            ')->execute([
                'file_uuid' => $file->getUuid()->getBytes(),
                'name' => $file->getName(),
                'path' => $file->getPath(),
                'mime_type' => $file->getMimeType(),
            ]);
            $this->pdo->commit();
            $file->metaDataSetTimestamps(new \DateTimeImmutable(), new \DateTimeImmutable());
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function update(File $file)
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
                'name' => $file->getName(),
                'path' => $file->getPath(),
                'mime_type' => $file->getMimeType(),
            ]);

            // When at least one of the fields changes, the rowCount will be 1 and an update occurred
            if ($query->rowCount() === 1) {
                $this->objectRepository->update(File::TYPE, $file->getUuid());
            }

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
        return (new File(
            Uuid::fromBytes($row['file_uuid']),
            $row['name'],
            $row['path'],
            $row['mime_type']
        ))->metaDataSetTimestamps(new \DateTimeImmutable($row['created']), new \DateTimeImmutable($row['updated']));
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
}
