<?php declare(strict_types=1);

namespace Spot\SiteContent\Repository;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\DataModel\Repository\NoUniqueResultException;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Value\PageStatusValue;

class PageRepository
{
    /** @var  \PDO */
    private $pdo;

    /** @var  ObjectRepository */
    private $objectRepository;

    public function __construct(\PDO $pdo, ObjectRepository $objectRepository)
    {
        $this->pdo = $pdo;
        $this->objectRepository = $objectRepository;
    }

    public function create(Page $page)
    {
        $this->pdo->beginTransaction();
        try {
            $this->objectRepository->create(Page::TYPE, $page->getUuid());
            $this->pdo->prepare('
                INSERT INTO pages (page_uuid, title, slug, short_title, parent_uuid, sort_order, status)
                    VALUES (:page_uuid, :title, :slug, :short_title, :parent_uuid, :sort_order, :status)
            ')->execute([
                'page_uuid' => $page->getUuid()->getBytes(),
                'title' => $page->getTitle(),
                'slug' => $page->getSlug(),
                'short_title' => $page->getShortTitle(),
                'parent_uuid' => $page->getParentUuid() ? $page->getParentUuid()->getBytes() : null,
                'sort_order' => $page->getSortOrder(),
                'status' => $page->getStatus()->toString(),
            ]);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function update(Page $page)
    {
        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare('
                UPDATE pages
                    SET title = :title,
                        slug = :slug,
                        short_title = :short_title,
                        sort_order = :sort_order,
                        status = :status
                    WHERE page_uuid = :page_uuid
            ');
            $query->execute([
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
        return new Page(
            Uuid::fromBytes($row['page_uuid']),
            $row['title'],
            $row['slug'],
            $row['short_title'],
            $row['parent_uuid'] ? Uuid::fromBytes($row['parent_uuid']) : null,
            intval($row['sort_order']),
            PageStatusValue::get($row['status'])
        );
    }

    public function getByUuid(UuidInterface $uuid) : Page
    {
        $query = $this->pdo->prepare('
            SELECT page_uuid, title, slug, short_title, parent_uuid, sort_order, status
                FROM pages
                WHERE page_uuid = :page_uuid
        ');
        $query->execute(['page_uuid' => $uuid->getBytes()]);

        if ($query->rowCount() !== 1) {
            throw new NoUniqueResultException('Expected a unique result, but got ' . $query->rowCount() . ' results.');
        }

        $page = $this->getPageFromRow($query->fetch(\PDO::FETCH_ASSOC));
        $this->getBlocksForPages([$page]);
        return $page;
    }

    public function getBySlug(string $slug) : Page
    {
        $query = $this->pdo->prepare('
            SELECT page_uuid, title, slug, short_title, parent_uuid, sort_order, status
                FROM pages
                WHERE slug = :slug
        ');
        $query->execute(['slug' => $slug]);

        if ($query->rowCount() !== 0) {
            throw new NoUniqueResultException('Expected a unique result, but got ' . $query->rowCount() . ' results.');
        }

        $page = $this->getPageFromRow($query->fetch(\PDO::FETCH_ASSOC));
        $this->getBlocksForPages([$page]);
        return $page;
    }

    /** @return  Page[] */
    public function getAllByParentUuid(UuidInterface $uuid = null) : array
    {
        if (!is_null($uuid)) {
            $query = $this->pdo->prepare('
                SELECT page_uuid, title, slug, short_title, parent_uuid, sort_order, status
                    FROM pages
                    WHERE parent_uuid = :parent_uuid
                    ORDER BY sort_order ASC
            ');
            $query->execute(['parent_uuid' => $uuid ? $uuid->getBytes() : null]);
        } else {
            $query = $this->pdo->prepare('
                SELECT page_uuid, title, slug, short_title, parent_uuid, sort_order, status
                    FROM pages
                    WHERE parent_uuid IS NULL
                    ORDER BY sort_order ASC
            ');
            $query->execute();
        }

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
            $this->pdo->prepare('
                INSERT INTO page_blocks (page_block_uuid, page_uuid, type, parameters, location, sort_order, status)
                    VALUES (:page_block_uuid, :page_uuid, :type, :parameters, :location, :sort_order, :status)
            ')->execute([
                'page_block_uuid' => $block->getUuid()->getBytes(),
                'page_uuid' => $block->getPage()->getUuid()->getBytes(),
                'type' => $block->getType(),
                'parameters' => json_encode($block->getParameters()),
                'location' => $block->getLocation(),
                'sort_order' => $page->getSortOrder(),
                'status' => $page->getStatus()->toString(),
            ]);
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
        $block->setStatus(PageStatusValue::get(PageStatusValue::DELETED));
    }

    private function getPageBlockFromRow(Page $page, array $row)
    {
        return new PageBlock(
            Uuid::fromBytes($row['page_block_uuid']),
            $page,
            $row['type'],
            !is_null($row['parameters']) ? json_decode($row['parameters'], true) : [],
            $row['location'],
            intval($row['sort_order']),
            PageStatusValue::get($row['status'])
        );
    }

    /**
     * @param   Page[] $pages
     * @return  void
     */
    public function getBlocksForPages(array $pages)
    {
        $uuids = [];
        /** @var  Page[] $pagesByUuid */
        $pagesByUuid = [];
        foreach ($pages as $page) {
            $uuids[] = $page->getUuid()->getBytes();
            $pagesByUuid[$page->getUuid()->getBytes()] = $page;
            $page->setBlocks([]);
        }

        $query = $this->pdo->prepare('
            SELECT page_block_uuid, page_uuid, type, parameters, location, sort_order, status
              FROM page_blocks
             WHERE page_uuid IN ("' . implode('", "', $uuids) . '")
          ORDER BY sort_order ASC
        ');
        $query->execute();

        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $page = $pagesByUuid[$row['page_uuid']];
            $page->addBlock($this->getPageBlockFromRow($page, $row));
        }
    }
}
