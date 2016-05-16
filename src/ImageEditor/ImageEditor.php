<?php declare(strict_types = 1);

namespace Spot\ImageEditor;

use Imagine\Exception\RuntimeException;
use Imagine\Image\AbstractImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Spot\FileManager\Entity\File;

class ImageEditor
{
    const MIME_PATTERN = '#^image/(jpeg|jpg|jpe|gif|png)$#ui';

    /** @var  AbstractImagine */
    private $imagine;

    /** @var  \Closure[]  indexed by operation name */
    private $operations;

    public function __construct(AbstractImagine $imagine)
    {
        $this->imagine = $imagine;
        $this->operations = $this->availableOperations();
    }

    /** @return  \Closure[]  indexed by operation name */
    protected function availableOperations() : array
    {
        return [
            'resize' => (new \ReflectionMethod($this, 'operationResize'))->getClosure($this),
            'crop' => (new \ReflectionMethod($this, 'operationCrop'))->getClosure($this),
            'rotate' => (new \ReflectionMethod($this, 'operationRotate'))->getClosure($this),
            'negative' => (new \ReflectionMethod($this, 'operationNegative'))->getClosure($this),
            'gamma' => (new \ReflectionMethod($this, 'operationGamma'))->getClosure($this),
            'greyscale' => (new \ReflectionMethod($this, 'operationGreyscale'))->getClosure($this),
            'blur' => (new \ReflectionMethod($this, 'operationBlur'))->getClosure($this),
        ];
    }

    protected function getOperation(string $operation) : \Closure
    {
        if (!isset($this->operations[$operation])) {
            throw new \RuntimeException('Unsupported image operation: ' . $operation);
        }
        return $this->operations[$operation];
    }

    public function isImage(File $file) : bool
    {
        return preg_match(self::MIME_PATTERN, $file->getMimeType()->toString()) !== 0;
    }

    public function determineImageFormat(File $file) : string
    {
        preg_match(self::MIME_PATTERN, $file->getMimeType()->toString(), $matches);
        $imageMimeType = $matches[1] ?? 'none';

        switch ($imageMimeType) {
            case 'jpg':
            case 'jpeg':
            case 'jpe':
                return 'jpeg';
            case 'png':
                return 'png';
            case 'gif':
                return 'gif';
            default:
                throw new RuntimeException('Invalid image type, cannot edit: ' . $file->getMimeType()->toString());
        }
    }

    /** @return  resource */
    public function process(File $file, array $operations) : ImageInterface
    {
        $image = $this->imagine->read($file->getStream());
        foreach ($operations as $operation => $args) {
            $this->executeOperation($image, $operation, $args);
        }
        return $image;
    }

    public function output(File $file, ImageInterface $image) : string
    {
        return $image->get($this->determineImageFormat($file));
    }

    private function executeOperation(ImageInterface $image, string $operationName, array $args)
    {
        $operation = new \ReflectionFunction($this->getOperation($operationName));
        $callArgs = [];
        foreach ($operation->getParameters() as $parameter) {
            if ($parameter->getName() === 'image') {
                $callArgs[] = $image;
            } elseif (array_key_exists($parameter->getName(), $args)) {
                $callArgs[] = $args[$parameter->getName()];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $callArgs[] = $parameter->getDefaultValue();
            } else {
                throw new \RuntimeException(
                    'Parameter ' . $parameter->getName() . ' missing for image operation ' . $operationName
                );
            }
        }
        return $operation->invokeArgs($callArgs);
    }

    protected function operationResize(ImageInterface $image, int $width, int $height)
    {
        $image->resize(new Box($width, $height));
    }

    protected function operationCrop(ImageInterface $image, int $x, int $y, int $width, int $height)
    {
        $image->crop(new Point($x, $y), new Box($width, $height));
    }

    protected function operationRotate(ImageInterface $image, int $degrees)
    {
        $image->rotate($degrees);
    }

    protected function operationNegative(ImageInterface $image, bool $apply)
    {
        if ($apply) {
            $image->effects()->negative();
        }
    }

    protected function operationGamma(ImageInterface $image, float $correction)
    {
        $image->effects()->gamma($correction);
    }

    protected function operationGreyscale(ImageInterface $image, bool $apply)
    {
        if ($apply) {
            $image->effects()->grayscale();
        }
    }

    protected function operationBlur(ImageInterface $image, float $amount)
    {
        $image->effects()->blur($amount);
    }
}
