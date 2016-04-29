<?php

namespace spec\Spot\SiteContent\Repository;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Repository\PageRepository;
use Spot\SiteContent\Value\PageStatusValue;

/** @mixin  PageRepository */
class PageRepositorySpec extends ObjectBehavior
{
    /** @var  \PDO */
    private $pdo;

    /** @var  \Spot\DataModel\Repository\ObjectRepository */
    private $objectRepository;

    public function let(\PDO $pdo, ObjectRepository $objectRepository)
    {
        $this->pdo = $pdo;
        $this->objectRepository = $objectRepository;
        $this->beConstructedWith($pdo, $objectRepository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PageRepository::class);
    }

    public function it_can_create_a_page(Page $page, \PDOStatement $statement)
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

    public function it_will_roll_back_on_error_during_create(Page $page)
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

    public function it_can_update_a_page(Page $page, \PDOStatement $statement)
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

    public function it_will_roll_back_on_error_during_update(Page $page)
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

    public function it_can_delete_a_page(Page $page)
    {
        $uuid = Uuid::uuid4();
        $page->getUuid()->willReturn($uuid);
        $page->setStatus(PageStatusValue::get('deleted'))->shouldBeCalled();

        $this->objectRepository->delete(Page::TYPE, $uuid)
            ->shouldBeCalled();

        $this->delete($page);
    }

    public function it_can_retrieve_a_page_by_uuid(\PDOStatement $pageStatement, \PDOStatement $blockStatement)
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

        $this->pdo->quote($uuid->getBytes())->willReturn($uuid->getBytes());
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

    public function it_can_retrieve_a_page_by_slug(\PDOStatement $pageStatement, \PDOStatement $blockStatement)
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

        $this->pdo->quote($uuid->getBytes())->willReturn($uuid->getBytes());
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

    public function it_can_get_pages_by_parent_uuid(\PDOStatement $pageStatement, \PDOStatement $blockStatement)
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

    public function it_can_get_pages_from_root(\PDOStatement $pageStatement, \PDOStatement $blockStatement)
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

        $this->pdo->quote($uuid1->getBytes())->willReturn($uuid1->getBytes());
        $this->pdo->quote($uuid2->getBytes())->willReturn($uuid2->getBytes());
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

    public function it_can_add_a_block_to_a_page(Page $page, PageBlock $block, \PDOStatement $statement)
    {
        $uuid = Uuid::uuid4();
        $page->getUuid()->willReturn($uuid);

        $blockUuid = Uuid::uuid4();
        $blockType = 'type';
        $blockLocation = 'main';
        $blockSortOrder = 42;
        $blockStatus = PageStatusValue::get('concept');
        $block->getUuid()->willReturn($blockUuid);
        $block->getPage()->willReturn($page);
        $block->getType()->willReturn($blockType);
        $block->getParameters()->willReturn([]);
        $block->getLocation()->willReturn($blockLocation);
        $block->getSortOrder()->willReturn($blockSortOrder);
        $block->getStatus()->willReturn($blockStatus);
        $block->metaDataSetInsertTimestamp(new Argument\Token\TypeToken(\DateTimeImmutable::class))
            ->shouldBeCalled();

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->objectRepository->create(PageBlock::TYPE, $blockUuid)
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('INSERT INTO page_blocks'))
            ->willReturn($statement);
        $statement->execute([
            'page_block_uuid' => $blockUuid->getBytes(),
            'page_uuid' => $uuid->getBytes(),
            'type' => $blockType,
            'parameters' => json_encode([]),
            'location' => $blockLocation,
            'sort_order' => $blockSortOrder,
            'status' => $blockStatus->toString(),
        ])->shouldBeCalled();

        $this->objectRepository->update(Page::TYPE, $uuid)
            ->shouldBeCalled();
        $this->pdo->commit()
            ->shouldBeCalled();

        $this->addBlockToPage($block, $page);
    }

    public function it_will_roll_back_when_adding_block_to_a_page_fails(Page $page, PageBlock $block)
    {
        $uuid = Uuid::uuid4();
        $page->getUuid()->willReturn($uuid);

        $blockUuid = Uuid::uuid4();
        $block->getUuid()->willReturn($blockUuid);
        $block->getPage()->willReturn($page);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->objectRepository->create(PageBlock::TYPE, $blockUuid)
            ->willThrow(new \RuntimeException());

        $this->pdo->rollBack()
            ->shouldBeCalled();

        $this->shouldThrow(\RuntimeException::class)->duringAddBlockToPage($block, $page);
    }

    public function it_will_error_when_trying_to_add_block_to_wrong_page(Page $page1, Page $page2, PageBlock $block)
    {
        $uuid1 = Uuid::uuid4();
        $page1->getUuid()->willReturn($uuid1);
        $uuid2 = Uuid::uuid4();
        $page2->getUuid()->willReturn($uuid2);

        $block->getPage()->willReturn($page2);

        $this->shouldThrow(\OutOfBoundsException::class)->duringAddBlockToPage($block, $page1);
    }

    public function it_can_update_a_block(Page $page, PageBlock $block, \PDOStatement $statement)
    {
        $uuid = Uuid::uuid4();
        $page->getUuid()->willReturn($uuid);
        $page->metaDataSetUpdateTimestamp(new Argument\Token\TypeToken(\DateTimeImmutable::class))
            ->shouldBeCalled();

        $blockUuid = Uuid::uuid4();
        $blockSortOrder = 42;
        $blockStatus = PageStatusValue::get('concept');
        $block->getUuid()->willReturn($blockUuid);
        $block->getPage()->willReturn($page);
        $block->getParameters()->willReturn([]);
        $block->getSortOrder()->willReturn($blockSortOrder);
        $block->getStatus()->willReturn($blockStatus);
        $block->metaDataSetUpdateTimestamp(new Argument\Token\TypeToken(\DateTimeImmutable::class))
            ->shouldBeCalled();

        $this->pdo->beginTransaction()
            ->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('UPDATE page_blocks'))
            ->willReturn($statement);
        $statement->execute([
            'page_block_uuid' => $blockUuid->getBytes(),
            'parameters' => json_encode([]),
            'sort_order' => $blockSortOrder,
            'status' => $blockStatus->toString(),
        ])->shouldBeCalled();
        $statement->rowCount()
            ->willReturn(1);

        $this->objectRepository->update(Page::TYPE, $uuid)
            ->shouldBeCalled();
        $this->objectRepository->update(PageBlock::TYPE, $blockUuid)
            ->shouldBeCalled();
        $this->pdo->commit()
            ->shouldBeCalled();

        $this->updateBlockForPage($block, $page);
    }

    public function it_will_roll_back_when_update_a_block_fails(Page $page, PageBlock $block)
    {
        $uuid = Uuid::uuid4();
        $page->getUuid()->willReturn($uuid);

        $blockUuid = Uuid::uuid4();
        $block->getUuid()->willReturn($blockUuid);
        $block->getPage()->willReturn($page);

        $this->pdo->beginTransaction()
            ->shouldBeCalled();
        $this->pdo->prepare(new Argument\Token\StringContainsToken('UPDATE page_blocks'))
            ->willThrow(new \RuntimeException());

        $this->pdo->rollBack()
            ->shouldBeCalled();

        $this->shouldThrow(\RuntimeException::class)->duringUpdateBlockForPage($block, $page);
    }

    /**
     * @param  \Spot\SiteContent\Entity\Page $page1
     * @param  \Spot\SiteContent\Entity\Page $page2
     * @param  \Spot\SiteContent\Entity\PageBlock $block
     */
    public function it_will_error_when_trying_to_update_a_block_to_wrong_page(
        Page $page1,
        Page $page2,
        PageBlock $block
    )
    {
        $uuid1 = Uuid::uuid4();
        $page1->getUuid()->willReturn($uuid1);
        $uuid2 = Uuid::uuid4();
        $page2->getUuid()->willReturn($uuid2);

        $block->getPage()->willReturn($page2);

        $this->shouldThrow(\OutOfBoundsException::class)->duringUpdateBlockForPage($block, $page1);
    }

    public function it_can_delete_a_block_from_a_page(Page $page, PageBlock $block)
    {
        $uuid = Uuid::uuid4();
        $page->getUuid()->willReturn($uuid);
        $page->removeBlock($block)->shouldBeCalled();

        $blockUuid = Uuid::uuid4();
        $block->getUuid()->willReturn($blockUuid);
        $block->getPage()->willReturn($page);
        $block->setStatus(PageStatusValue::get('deleted'))->shouldBeCalled();

        $this->objectRepository->delete(PageBlock::TYPE, $blockUuid)
            ->shouldBeCalled();
        $this->objectRepository->update(Page::TYPE, $uuid)
            ->shouldBeCalled();

        $this->deleteBlockFromPage($block, $page);
    }

    public function it_will_error_when_trying_to_delete_a_block_from_wrong_page(
        Page $page1,
        Page $page2,
        PageBlock $block
    )
    {
        $uuid1 = Uuid::uuid4();
        $page1->getUuid()->willReturn($uuid1);
        $uuid2 = Uuid::uuid4();
        $page2->getUuid()->willReturn($uuid2);

        $block->getPage()->willReturn($page2);

        $this->shouldThrow(\OutOfBoundsException::class)->duringDeleteBlockFromPage($block, $page1);
    }
}
