<?php declare(strict_types = 1);

namespace Spot\SiteContent\BlockType;

use Particle\Validator\Validator;
use Ramsey\Uuid\Uuid;
use Spot\Api\Request\RequestInterface;
use Spot\Application\Response\ValidationFailedException;
use Spot\SiteContent\Entity\Page;
use Spot\SiteContent\Entity\PageBlock;
use Spot\SiteContent\Value\PageStatusValue;

class HtmlContentBlockType implements BlockTypeInterface
{
    const TYPE = 'htmlContent';

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
            ['content' => null, 'wysiwyg' => true],
            $location,
            $sortOrder,
            $status
        );
    }

    public function validate(PageBlock $block, RequestInterface $request)
    {
        $validator = new Validator();
        $validator->required('content');
        $validator->optional('wysiwyg')->bool();
        $result = $validator->validate($block->getParameters());
        if (!$result->isValid()) {
            throw new ValidationFailedException($result, $request);
        }
    }
}
