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
        $title = 'Thor\'s Hammer';
        $slug = '1x09_thors_hammer';
        $shortTitle = '1x09';
        $sortOrder = 109;
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
        $title = 'The Torment of Tantalus';
        $slug = '1x10_the_torment_of_tantalus';
        $shortTitle = '1x10';
        $sortOrder = 110;
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

    /**
     * @param  \Spot\SiteContent\Entity\Page $page
     */
    public function it_canDeleteAPage($page)
    {
        $uuid = Uuid::uuid4();
        $page->getUuid()->willReturn($uuid);
        $page->setStatus(PageStatusValue::get('deleted'))->shouldBeCalled();

        $this->objectRepository->delete(Page::TYPE, $uuid)
            ->shouldBeCalled();

        $this->delete($page);
    }

    /**
     * @param  \PDOStatement $pageStatement
     * @param  \PDOStatement $blockStatement
     */
    public function it_canRetrieveAPageByUuid($pageStatement, $blockStatement)
    {
        $uuid = Uuid::uuid4();
        $title = 'Bloodlines';
        $slug = '1x11_bloodlines';
        $shortTitle = '1x11';
        $parentUuid = Uuid::uuid4();
        $sortOrder = 111;
        $status = 'published';
        $ts = new \DateTimeImmutable();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM pages'))
            ->willReturn($pageStatement);
        $pageStatement->execute(['page_uuid' => $uuid->getBytes()])
            ->shouldBeCalled();
        $pageStatement->rowCount()
            ->willReturn(1);
        $pageStatement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    'page_uuid' => $uuid->getBytes(),
                    'title' => $title,
                    'slug' => $slug,
                    'short_title' => $shortTitle,
                    'parent_uuid' => $parentUuid->getBytes(),
                    'sort_order' => strval($sortOrder),
                    'status' => $status,
                    'created' => $ts->format('c'),
                    'updated' => $ts->format('c'),
                ],
                false
            );

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM page_blocks'))
            ->willReturn($blockStatement);
        $blockStatement->execute([])
            ->shouldBeCalled();
        $blockStatement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(false);

        $page = $this->getByUuid($uuid);
        $page->shouldHaveType(Page::class);
        $page->getUuid()->toString()->shouldReturn($uuid->toString());
        $page->getTitle()->shouldReturn($title);
        $page->getSlug()->shouldReturn($slug);
        $page->getShortTitle()->shouldReturn($shortTitle);
        $page->getParentUuid()->toString()->shouldReturn($parentUuid->toString());
        $page->getSortOrder()->shouldReturn($sortOrder);
        $page->getStatus()->toString()->shouldReturn($status);
        $page->metaDataGetCreatedTimestamp()->format('c')->shouldReturn($ts->format('c'));
        $page->metaDataGetUpdatedTimestamp()->format('c')->shouldReturn($ts->format('c'));
        $page->getBlocks()->shouldReturn([]);
    }

    /**
     * @param  \PDOStatement $pageStatement
     * @param  \PDOStatement $blockStatement
     */
    public function it_canRetrieveAPageBySlug($pageStatement, $blockStatement)
    {
        $uuid = Uuid::uuid4();
        $title = 'Bloodlines';
        $slug = '1x11_bloodlines';
        $shortTitle = '1x11';
        $parentUuid = Uuid::uuid4();
        $sortOrder = 111;
        $status = 'published';
        $ts = new \DateTimeImmutable();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM pages'))
            ->willReturn($pageStatement);
        $pageStatement->execute(['slug' => $slug])
            ->shouldBeCalled();
        $pageStatement->rowCount()
            ->willReturn(1);
        $pageStatement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    'page_uuid' => $uuid->getBytes(),
                    'title' => $title,
                    'slug' => $slug,
                    'short_title' => $shortTitle,
                    'parent_uuid' => $parentUuid->getBytes(),
                    'sort_order' => strval($sortOrder),
                    'status' => $status,
                    'created' => $ts->format('c'),
                    'updated' => $ts->format('c'),
                ],
                false
            );

        $blockUuid = Uuid::uuid4();
        $blockType = 'type';
        $blockParameters = ['answer' => 42, 'thx' => 1138];
        $blockLocation = 'sidebar';
        $blockSortOrder = 42;
        $blockStatus = 'concept';

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM page_blocks'))
            ->willReturn($blockStatement);
        $blockStatement->execute([])
            ->shouldBeCalled();
        $blockStatement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    'page_block_uuid' => $blockUuid->getBytes(),
                    'page_uuid' => $uuid->getBytes(),
                    'type' => $blockType,
                    'parameters' => json_encode($blockParameters),
                    'location' => $blockLocation,
                    'sort_order' => strval($blockSortOrder),
                    'status' => $blockStatus,
                    'created' => $ts->format('c'),
                    'updated' => $ts->format('c'),
                ],
                false
            );

        $page = $this->getBySlug($slug);
        $page->shouldHaveType(Page::class);
        $page->getUuid()->toString()->shouldReturn($uuid->toString());
        $page->getTitle()->shouldReturn($title);
        $page->getSlug()->shouldReturn($slug);
        $page->getShortTitle()->shouldReturn($shortTitle);
        $page->getParentUuid()->toString()->shouldReturn($parentUuid->toString());
        $page->getSortOrder()->shouldReturn($sortOrder);
        $page->getStatus()->toString()->shouldReturn($status);
        $page->metaDataGetCreatedTimestamp()->format('c')->shouldReturn($ts->format('c'));
        $page->metaDataGetUpdatedTimestamp()->format('c')->shouldReturn($ts->format('c'));

        $block = $page->getBlocks()[0];
        $block->getUuid()->toString()->shouldReturn($blockUuid->toString());
        $block->getPage()->shouldReturn($page);
        $block->getType()->shouldReturn($blockType);
        $block->getParameters()->shouldReturn($blockParameters);
        $block->getLocation()->shouldReturn($blockLocation);
        $block->getSortOrder()->shouldReturn($blockSortOrder);
        $block->getStatus()->toString()->shouldReturn($blockStatus);
        $block->metaDataGetCreatedTimestamp()->format('c')->shouldReturn($ts->format('c'));
        $block->metaDataGetUpdatedTimestamp()->format('c')->shouldReturn($ts->format('c'));
    }

    /**
     * @param  \PDOStatement $pageStatement
     * @param  \PDOStatement $blockStatement
     */
    public function it_canGetPagesByParentUuid($pageStatement, $blockStatement)
    {
        $parentUuid = Uuid::uuid4();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM pages'))
            ->willReturn($pageStatement);
        $pageStatement->execute(['parent_uuid' => $parentUuid->getBytes()])
            ->shouldBeCalled();
        $pageStatement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM page_blocks'))
            ->willReturn($blockStatement);
        $blockStatement->execute([])
            ->shouldBeCalled();
        $blockStatement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->getAllByParentUuid($parentUuid)->shouldReturn([]);
    }

    /**
     * @param  \PDOStatement $pageStatement
     * @param  \PDOStatement $blockStatement
     */
    public function it_canGetPagesFromRoot($pageStatement, $blockStatement)
    {
        $uuid1 = Uuid::uuid4();
        $title1 = 'Fire and Water';
        $slug1 = '1x12_fire_and_water';
        $shortTitle1 = '1x12';
        $sortOrder1 = 112;
        $status1 = 'published';
        $ts1 = new \DateTimeImmutable('2015-12-25 02:34:56');

        $uuid2 = Uuid::uuid4();
        $title2 = 'The Nox';
        $slug2 = '1x13_the_nox';
        $shortTitle2 = '1x13';
        $sortOrder2 = 113;
        $status2 = 'published';
        $ts2 = new \DateTimeImmutable('2015-12-24 01:23:45');

        $blockUuid = Uuid::uuid4();
        $blockType = 'type';
        $blockParameters = ['answer' => 42, 'thx' => 1138];
        $blockLocation = 'sidebar';
        $blockSortOrder = 42;
        $blockStatus = 'concept';

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM pages'))
            ->willReturn($pageStatement);
        $pageStatement->execute([])
            ->shouldBeCalled();
        $pageStatement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    'page_uuid' => $uuid1->getBytes(),
                    'title' => $title1,
                    'slug' => $slug1,
                    'short_title' => $shortTitle1,
                    'parent_uuid' => null,
                    'sort_order' => strval($sortOrder1),
                    'status' => $status1,
                    'created' => $ts1->format('c'),
                    'updated' => $ts1->format('c'),
                ],
                [
                    'page_uuid' => $uuid2->getBytes(),
                    'title' => $title2,
                    'slug' => $slug2,
                    'short_title' => $shortTitle2,
                    'parent_uuid' => null,
                    'sort_order' => strval($sortOrder2),
                    'status' => $status2,
                    'created' => $ts2->format('c'),
                    'updated' => $ts2->format('c'),
                ],
                false
            );

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM page_blocks'))
            ->willReturn($blockStatement);
        $blockStatement->execute([])
            ->shouldBeCalled();
        $blockStatement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    'page_block_uuid' => $blockUuid->getBytes(),
                    'page_uuid' => $uuid1->getBytes(),
                    'type' => $blockType,
                    'parameters' => json_encode($blockParameters),
                    'location' => $blockLocation,
                    'sort_order' => strval($blockSortOrder),
                    'status' => $blockStatus,
                    'created' => $ts2->format('c'),
                    'updated' => $ts2->format('c'),
                ],
                false
            );

        $pages = $this->getAllByParentUuid(null);

        $page1 = $pages[0];
        $page1->shouldHaveType(Page::class);
        $page1->getUuid()->toString()->shouldReturn($uuid1->toString());
        $page1->getParentUuid()->shouldReturn(null);
        $blocks = $page1->getBlocks();
        $blocks->shouldHaveCount(1);
        $blocks[0]->getUuid()->toString()->shouldReturn($blockUuid->toString());

        $page2 = $pages[1];
        $page2->shouldHaveType(Page::class);
        $page2->getUuid()->toString()->shouldReturn($uuid2->toString());
        $page2->getParentUuid()->shouldReturn(null);
        $page2->getBlocks()->shouldReturn([]);
    }
}
