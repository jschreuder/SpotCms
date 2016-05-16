<?php

namespace spec\Spot\ImageEditor\Repository;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Spot\FileManager\Entity\File;
use Spot\FileManager\Repository\FileRepository;
use Spot\FileManager\Value\FileNameValue;
use Spot\FileManager\Value\FilePathValue;
use Spot\FileManager\Value\MimeTypeValue;
use Spot\ImageEditor\Repository\ImageRepository;

/** @mixin  ImageRepository */
class ImageRepositorySpec extends ObjectBehavior
{
    /** @var  \PDO */
    private $pdo;

    /** @var  FileRepository */
    private $fileRepository;

    public function let(\PDO $pdo, FileRepository $fileRepository)
    {
        $this->pdo = $pdo;
        $this->fileRepository = $fileRepository;
        $this->beConstructedWith($pdo, $fileRepository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ImageRepository::class);
    }

    public function it_can_retrieve_an_image(File $file)
    {
        $path = '/path/to/file.ext';
        $this->fileRepository->getByFullPath($path)->willReturn($file);
        $this->getByFullPath($path)->shouldReturn($file);
    }

    public function it_can_create_an_image(File $oldFile)
    {
        $name = FileNameValue::get('oldName.jpg');
        $path = FilePathValue::get('/path/to/something');
        $mime = MimeTypeValue::get('image/jpg');
        $oldFile->getName()->willReturn($name);
        $oldFile->getPath()->willReturn($path);
        $oldFile->getMimeType()->willReturn($mime);
        $imageContents = tmpfile();
        $this->fileRepository->createFromUpload(new Argument\Token\TypeToken(File::class));
        $newImage = $this->createImage($oldFile, $imageContents);
        $newImage->getUuid()->shouldHaveType(Uuid::class);
        $newImage->getName()->shouldReturn($name);
        $newImage->getPath()->shouldReturn($path);
        $newImage->getMimeType()->shouldReturn($mime);
        $newImage->getStream()->shouldReturn($imageContents);
    }

    public function it_can_get_multiple_images_in_path(\PDOStatement $statement)
    {
        $path = '/sg-1/season1';
        $name1 = 'the-broca-divide.jpg';
        $name2 = 'the-first-commandment.png';
        $name3 = 'cold-lazarus.gif';

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

        $files = $this->getImageNamesInPath($path);

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

        $this->getImageNamesInPath($path)->shouldHaveCount(0);
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
