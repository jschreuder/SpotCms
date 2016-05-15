<?php declare(strict_types = 1);

namespace Spot\ImageEditor\Repository;

use Spot\DataModel\Repository\SqlRepositoryTrait;
use Spot\FileManager\Entity\File;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Value\FilePathValue;

class ImageRepository
{
    use SqlRepositoryTrait;

    /** @var  \PDO */
    private $pdo;

    /** @var  FileRepository */
    private $fileRepository;

    public function __construct(\PDO $pdo, FileRepository $fileRepository)
    {
        $this->pdo = $pdo;
        $this->fileRepository = $fileRepository;
    }

    public function getByFullPath(string $path) : File
    {
        $file = $this->fileRepository->getByFullPath($path);
        if (!$file->isImage()) {
            throw new \RuntimeException('File found, but is not of a supported image type: ' . $path);
        }
        return $file;
    }

    /** @return  string[] */
    public function getImageNamesInPath(string $path) : array
    {
        $query = $this->executeSql('
                SELECT name
                  FROM files
                 WHERE path = :path
                   AND mime_type LIKE "image/%"
              ORDER BY name ASC
        ', ['path' => $path]);

        $fileNames = [];
        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $fileNames[] = $row['name'];
        }
        return $fileNames;
    }

    /** @return  string[] */
    public function getDirectoriesInPath(string $path) : array
    {
        $query = $this->executeSql('
              SELECT path
                FROM files
               WHERE mime_type LIKE "image/%"
                 AND path LIKE CONCAT(:path, "%")
                 AND path != :path
            GROUP BY path
            ORDER BY path ASC
        ', ['path' => $path]);

        /** @var  FilePathValue[] $directories */
        $directories = [];
        while ($directory = $query->fetchColumn()) {
            if (count($directories) > 0 && strpos($directory, end($directories)->toString()) === 0) {
                continue;
            }
            $directories[] = FilePathValue::get($directory);
        }
        return $directories;
    }
}
