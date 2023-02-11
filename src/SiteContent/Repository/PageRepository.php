<?php declare(strict_types = 1);

namespace Spot\SiteContent\Repository;

use PDO;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\DataModel\Repository\SqlRepositoryTrait;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Value\PageStatusValue;

class PageRepository
{
    use SqlRepositoryTrait;

    public function __construct(PDO $pdo, private ObjectRepository $objectRepository)
    {
        $this->pdo = $pdo;
    }

    public function create(Page $page)
    {
        $this->pdo->beginTransaction();
        try {
            $this->objectRepository->create(Page::TYPE, $page->getUuid());
            $this->executeSql('
                INSERT INTO pages (page_uuid, title, slug, short_title, parent_uuid, sort_order, status)
                     VALUES (:page_uuid, :title, :slug, :short_title, :parent_uuid, :sort_order, :status)
            ', [
                'page_uuid' => $page->getUuid()->getBytes(),
                'title' => $page->getTitle(),
                'slug' => $page->getSlug(),
                'short_title' => $page->getShortTitle(),
                'parent_uuid' => $page->getParentUuid() ? $page->getParentUuid()->getBytes() : null,
                'sort_order' => $page->getSortOrder(),
                'status' => $page->getStatus()->toString(),
            ]);
            $this->pdo->commit();
            $page->metaDataSetInsertTimestamp(new \DateTimeImmutable());
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function update(Page $page)
    {
        $this->pdo->beginTransaction();
        try {
            $query = $this->executeSql('
                UPDATE pages
                   SET title = :title,
                       slug = :slug,
                       short_title = :short_title,
                       sort_order = :sort_order,
                       status = :status
                 WHERE page_uuid = :page_uuid
            ', [
                'page_uuid' => $page->getUuid()->getBytes(),
                'title' => $page->getTitle(),
                'slug' => $page->getSlug(),
                'short_title' => $page->getShortTitle(),
                'sort_order' => $page->getSortOrder(),
                'status' => $page->getStatus()->toString(),
            ]);

            // When at least one of the fields changes, the rowCount will be 1 and an update occurred
            if ($query->rowCount() === 1) {
                $this->objectRepository->update(Page::TYPE, $page->getUuid());
                $page->metaDataSetUpdateTimestamp(new \DateTimeImmutable());
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function delete(Page $page)
    {
        // The database constraint should cascade the delete to the page
        $this->objectRepository->delete(Page::TYPE, $page->getUuid());
        $page->setStatus(PageStatusValue::get(PageStatusValue::DELETED));
    }

    private function getPageFromRow(array $row) : Page
    {
        return (new Page(
                Uuid::fromBytes($row['page_uuid']),
                $row['title'],
                $row['slug'],
                $row['short_title'],
                $row['parent_uuid'] ? Uuid::fromBytes($row['parent_uuid']) : null,
                intval($row['sort_order']),
                PageStatusValue::get($row['status'])
            ))
            ->metaDataSetInsertTimestamp(new \DateTimeImmutable($row['created']))
            ->metaDataSetUpdateTimestamp(new \DateTimeImmutable($row['updated']));
    }

    public function getByUuid(UuidInterface $uuid) : Page
    {
        $page = $this->getPageFromRow($this->getRow('
                SELECT page_uuid, title, slug, short_title, parent_uuid, sort_order, status, created, updated
                  FROM pages
            INNER JOIN objects ON (page_uuid = uuid AND type = "pages")
                 WHERE page_uuid = :page_uuid
        ', ['page_uuid' => $uuid->getBytes()]));
        $this->getBlocksForPages([$page]);
        return $page;
    }

    public function getBySlug(string $slug) : Page
    {
        $page = $this->getPageFromRow($this->getRow('
                SELECT page_uuid, title, slug, short_title, parent_uuid, sort_order, status, created, updated
                  FROM pages
            INNER JOIN objects ON (page_uuid = uuid AND type = "pages")
                 WHERE slug = :slug
        ', ['slug' => $slug]));
        $this->getBlocksForPages([$page]);
        return $page;
    }

    /** @return  Page[] */
    public function getAllByParentUuid(UuidInterface $uuid = null) : array
    {
        if (is_null($uuid)) {
            $sql = '
                    SELECT page_uuid, title, slug, short_title, parent_uuid, sort_order, status, created, updated
                      FROM pages
                INNER JOIN objects ON (page_uuid = uuid AND type = "pages")
                     WHERE parent_uuid IS NULL
                  ORDER BY sort_order ASC
            ';
            $parameters = [];
        } else {
            $sql = '
                    SELECT page_uuid, title, slug, short_title, parent_uuid, sort_order, status, created, updated
                      FROM pages
                INNER JOIN objects ON (page_uuid = uuid AND type = "pages")
                     WHERE parent_uuid = :parent_uuid
                  ORDER BY sort_order ASC
            ';
            $parameters = ['parent_uuid' => $uuid->getBytes()];
        }
        $query = $this->executeSql($sql, $parameters);

        $pages = [];
        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $pages[] = $this->getPageFromRow($row);
        }
        $this->getBlocksForPages($pages);
        return $pages;
    }

    public function addBlockToPage(PageBlock $block, Page $page)
    {
        if (!$page->getUuid()->equals($block->getPage()->getUuid())) {
            throw new \OutOfBoundsException('PageBlock must belong to page to be added to it.');
        }

        $this->pdo->beginTransaction();
        try {
            $this->objectRepository->create(PageBlock::TYPE, $block->getUuid());
            $this->executeSql('
                INSERT INTO page_blocks (page_block_uuid, page_uuid, type, parameters, location, sort_order, status)
                     VALUES (:page_block_uuid, :page_uuid, :type, :parameters, :location, :sort_order, :status)
            ', [
                'page_block_uuid' => $block->getUuid()->getBytes(),
                'page_uuid' => $block->getPage()->getUuid()->getBytes(),
                'type' => $block->getType(),
                'parameters' => json_encode($block->getParameters()),
                'location' => $block->getLocation(),
                'sort_order' => $block->getSortOrder(),
                'status' => $block->getStatus()->toString(),
            ]);
            $this->objectRepository->update(Page::TYPE, $page->getUuid());
            $page->metaDataSetUpdateTimestamp(new \DateTimeImmutable());
            $this->pdo->commit();
            $block->metaDataSetInsertTimestamp(new \DateTimeImmutable());
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function updateBlockForPage(PageBlock $block, Page $page)
    {
        if (!$page->getUuid()->equals($block->getPage()->getUuid())) {
            throw new \OutOfBoundsException('PageBlock must belong to page to be added to it.');
        }

        $this->pdo->beginTransaction();
        try {
            $query = $this->executeSql('
                UPDATE page_blocks
                   SET parameters = :parameters,
                       sort_order = :sort_order,
                       status = :status
                 WHERE page_block_uuid = :page_block_uuid
            ', [
                'page_block_uuid' => $block->getUuid()->getBytes(),
                'parameters' => json_encode($block->getParameters()),
                'sort_order' => $block->getSortOrder(),
                'status' => $block->getStatus()->toString(),
            ]);

            // When at least one of the fields changes, the rowCount will be 1 and an update occurred
            if ($query->rowCount() === 1) {
                $this->objectRepository->update(Page::TYPE, $page->getUuid());
                $page->metaDataSetUpdateTimestamp(new \DateTimeImmutable());
                $this->objectRepository->update(PageBlock::TYPE, $block->getUuid());
                $block->metaDataSetUpdateTimestamp(new \DateTimeImmutable());
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function deleteBlockFromPage(PageBlock $block, Page $page)
    {
        if (!$page->getUuid()->equals($block->getPage()->getUuid())) {
            throw new \OutOfBoundsException('PageBlock must belong to page to be added to it.');
        }

        // The database constraint should cascade the delete to the page
        $this->objectRepository->delete(PageBlock::TYPE, $block->getUuid());
        $page->removeBlock($block);
        $this->objectRepository->update(Page::TYPE, $page->getUuid());
        $page->metaDataSetUpdateTimestamp(new \DateTimeImmutable());
        $block->setStatus(PageStatusValue::get(PageStatusValue::DELETED));
    }

    private function getPageBlockFromRow(Page $page, array $row) : PageBlock
    {
        return (new PageBlock(
                Uuid::fromBytes($row['page_block_uuid']),
                $page,
                $row['type'],
                !is_null($row['parameters']) ? json_decode($row['parameters'], true) : [],
                $row['location'],
                intval($row['sort_order']),
                PageStatusValue::get($row['status'])
            ))
            ->metaDataSetInsertTimestamp(new \DateTimeImmutable($row['created']))
            ->metaDataSetUpdateTimestamp(new \DateTimeImmutable($row['updated']));
    }

    /**
     * @param   Page[] $pages
     * @return  void
     */
    private function getBlocksForPages(array $pages)
    {
        $uuids = [];
        /** @var  Page[] $pagesByUuid */
        $pagesByUuid = [];
        foreach ($pages as $page) {
            $uuids[] = $this->pdo->quote($page->getUuid()->getBytes());
            $pagesByUuid[$page->getUuid()->getBytes()] = $page;
            $page->setBlocks([]);
        }

        $query = $this->executeSql('
                SELECT page_block_uuid, page_uuid, pb.type, parameters, location, sort_order, status, created, updated
                  FROM page_blocks pb
            INNER JOIN objects o ON (page_block_uuid = uuid AND o.type = "pageBlocks")
                 WHERE page_uuid IN ("' . implode('", "', $uuids) . '")
              ORDER BY sort_order ASC
        ', []);

        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $page = $pagesByUuid[$row['page_uuid']];
            $page->addBlock($this->getPageBlockFromRow($page, $row));
        }
    }
}
