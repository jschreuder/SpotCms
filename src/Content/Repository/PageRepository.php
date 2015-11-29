<?php declare(strict_types=1);

namespace Spot\Api\Content\Repository;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Api\Common\Repository\NoUniqueResultException;
use Spot\Api\Common\Repository\ObjectRepository;
use Spot\Api\Content\Entity\Page;
use Spot\Api\Content\Value\PageStatusValue;

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
                'parent_uuid' => $page->getParentUuid(),
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
                $this->objectRepository->update($page->getUuid());
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
        $this->objectRepository->delete($page->getUuid());
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

        return $this->getPageFromRow($query->fetch(\PDO::FETCH_ASSOC));
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

        return $this->getPageFromRow($query->fetch(\PDO::FETCH_ASSOC));
    }

    /** @return  Page[] */
    public function getAllByParentUuid(UuidInterface $uuid = null) : array
    {
        $query = $this->pdo->prepare('
            SELECT page_uuid, title, slug, short_title, parent_uuid, sort_order, status
                FROM pages
                WHERE parent_uuid = :parent_uuid
                ORDER BY sort_order ASC
        ');
        $query->execute(['parent_uuid' => $uuid ? $uuid->getBytes() : null]);

        $pages = [];
        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $pages[] = $this->getPageFromRow($query->fetch(\PDO::FETCH_ASSOC));
        }
        return $pages;
    }
}
