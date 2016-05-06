<?php declare(strict_types = 1);

namespace Spot\SiteContent\BlockType;

use Particle\Validator\Validator;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Application\Response\ValidationFailedException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Value\PageStatusValue;

class YoutubeBlockType implements BlockTypeInterface
{
    const TYPE = 'youtube';

    public function getTypeName() : string
    {
        return self::TYPE;
    }

    public function newBlock(Page $page, string $location, int $sortOrder, PageStatusValue $status) : PageBlock
    {
        return new PageBlock(
            Uuid::uuid4(),
            $page,
            $this->getTypeName(),
            ['youtubeUrl' => null],
            $location,
            $sortOrder,
            $status
        );
    }

    public function validate(PageBlock $block, RequestInterface $request)
    {
        $validator = new Validator();
        $validator->required('youtubeUrl')
            ->regex('#^https:\/\/(www\.youtube\.com\/watch\?v=|youtu\.be\/)[a-z0-9_]+#uiD');
        $result = $validator->validate($block->getParameters());
        if (!$result->isValid()) {
            throw new ValidationFailedException($result, $request);
        }
    }
}
