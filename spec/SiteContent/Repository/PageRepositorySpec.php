<?php

namespace spec\Spot\SiteContent\Repository;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  PageRepository */
class PageRepositorySpec extends ObjectBehavior
{
    /** @var  \PDO */
    private $pdo;

    /** @var  \Spot\DataModel\Repository\ObjectRepository */
    private $objectRepository;

    /**
     * @param  \PDO $pdo
     * @param  \Spot\DataModel\Repository\ObjectRepository $objectRepository
     */
    public function let($pdo, $objectRepository)
    {
        $this->pdo = $pdo;
        $this->objectRepository = $objectRepository;
        $this->beConstructedWith($pdo, $objectRepository);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(PageRepository::class);
    }

    /**
     * @param  \Spot\SiteContent\Entity\Page $page
     * @param  \PDOStatement $statement
     */
    public function it_canCreateAPage($page, $statement)
    {
        $uuid = Uuid::uuid4();
        $title = 'Cold Lazarus';
        $slug = '1x08_cold_lazarus';
        $shortTitle = '1x08';
        $parentUuid = Uuid::uuid4();
        $sortOrder = 108;
        $status = PageStatusValue::get('published');
        $page->getUuid()->willReturn($uuid);
        $page->getTitle()->willReturn($title);
        $page->getSlug()->willReturn($slug);
        $page->getShortTitle()->willReturn($shortTitle);
        $page->getParentUuid()->willReturn($parentUuid);
        $page->getSortOrder()->willReturn($sortOrder);
        $page->getStatus()->willReturn($status);
        $page->metaDataSetInsertTimestamp(new Argument\Token\TypeToken(\DateTimeImmutable::class))
            ->willReturn($page);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->objectRepository->create(Page::TYPE, $uuid)
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('INSERT INTO pages'))
            ->willReturn($statement);
        $statement->execute([
            'page_uuid' => $uuid->getBytes(),
            'title' => $title,
            'slug' => $slug,
            'short_title' => $shortTitle,
            'parent_uuid' => $parentUuid->getBytes(),
            'sort_order' => $sortOrder,
            'status' => $status->toString(),
        ])->shouldBeCalled();

        $this->pdo->commit()
            ->shouldBeCalled();


        $this->create($page);
    }

    /**
     * @param  \Spot\SiteContent\Entity\Page $page
     */
    public function it_willRollBackOnErrorDuringCreate($page)
    {
        $uuid = Uuid::uuid4();
        $page->getUuid()->willReturn($uuid);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->objectRepository->create(Page::TYPE, $uuid)
            ->willThrow(new \RuntimeException());

        $this->pdo->rollBack()
            ->shouldBeCalled();

        $this->shouldThrow(\RuntimeException::class)->duringCreate($page);
    }

    /**
     * @param  \Spot\SiteContent\Entity\Page $page
     * @param  \PDOStatement $statement
     */
    public function it_canUpdateAPage($page, $statement)
    {
        $uuid = Uuid::uuid4();
        $title = 'Cold Lazarus';
        $slug = '1x08_cold_lazarus';
        $shortTitle = '1x08';
        $sortOrder = 108;
        $status = PageStatusValue::get('published');
        $page->getUuid()->willReturn($uuid);
        $page->getTitle()->willReturn($title);
        $page->getSlug()->willReturn($slug);
        $page->getShortTitle()->willReturn($shortTitle);
        $page->getSortOrder()->willReturn($sortOrder);
        $page->getStatus()->willReturn($status);
        $page->metaDataSetUpdateTimestamp(new Argument\Token\TypeToken(\DateTimeImmutable::class))
            ->willReturn($page);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('UPDATE pages'))
            ->willReturn($statement);
        $statement->execute([
            'page_uuid' => $uuid->getBytes(),
            'title' => $title,
            'slug' => $slug,
            'short_title' => $shortTitle,
            'sort_order' => $sortOrder,
            'status' => $status->toString(),
        ])->shouldBeCalled();
        $statement->rowCount()
            ->willReturn(1);

        $this->objectRepository->update(Page::TYPE, $uuid)
            ->shouldBeCalled();
        $this->pdo->commit()
            ->shouldBeCalled();

        $this->update($page);
    }

    /**
     * @param  \Spot\SiteContent\Entity\Page $page
     */
    public function it_willRollBackOnErrorDuringUpdate($page)
    {
        $uuid = Uuid::uuid4();
        $title = 'Cold Lazarus';
        $slug = '1x08_cold_lazarus';
        $shortTitle = '1x08';
        $sortOrder = 108;
        $status = PageStatusValue::get('published');
        $page->getUuid()->willReturn($uuid);
        $page->getTitle()->willReturn($title);
        $page->getSlug()->willReturn($slug);
        $page->getShortTitle()->willReturn($shortTitle);
        $page->getSortOrder()->willReturn($sortOrder);
        $page->getStatus()->willReturn($status);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('UPDATE pages'))
            ->willThrow(new \RuntimeException());
        $this->pdo->rollBack()
            ->shouldBeCalled();

        $this->shouldThrow(\RuntimeException::class)->duringUpdate($page);
    }
}
