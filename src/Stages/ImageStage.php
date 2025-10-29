<?php

declare(strict_types=1);

namespace Bnussbau\TrmnlPipeline\Stages;

use Bnussbau\TrmnlPipeline\Exceptions\ProcessingException;
use Bnussbau\TrmnlPipeline\Model;
use Bnussbau\TrmnlPipeline\StageInterface;
use Bnussbau\TrmnlPipeline\TrmnlPipeline;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;

/**
 * Image stage for format conversion
 */
class ImageStage implements StageInterface
{
    /**
     * Default fallback values for image processing
     */
    private const DEFAULT_WIDTH = 800;

    private const DEFAULT_HEIGHT = 480;

    private const DEFAULT_COLORS = 2;

    private const DEFAULT_BIT_DEPTH = 1;

    private const DEFAULT_ROTATION = 0;

    private const DEFAULT_OFFSET_X = 0;

    private const DEFAULT_OFFSET_Y = 0;

    private const DEFAULT_FORMAT = 'png';

    private ?string $format = null;

    private ?int $width = null;

    private ?int $height = null;

    private ?int $colors = null;

    private ?int $bitDepth = null;

    private ?int $rotation = null;

    private ?int $offsetX = null;

    private ?int $offsetY = null;

    private ?string $outputPath = null;

    /**
     * @var array<string>|null
     */
    private ?array $colormap = null;

    /**
     * Set output format
     */
    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get output format (for testing)
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * Set final image width
     */
    public function width(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Set final image height
     */
    public function height(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Set number of colors
     */
    public function colors(int $colors): self
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * Set bit depth
     */
    public function bitDepth(int $depth): self
    {
        $this->bitDepth = $depth;

        return $this;
    }

    /**
     * Set image rotation in degrees
     */
    public function rotation(int $degrees): self
    {
        $this->rotation = $degrees;

        return $this;
    }

    /**
     * Set horizontal offset for image translation (positive = right, negative = left)
     */
    public function offsetX(int $offset): self
    {
        $this->offsetX = $offset;

        return $this;
    }

    /**
     * Set vertical offset for image translation (positive = down, negative = up)
     */
    public function offsetY(int $offset): self
    {
        $this->offsetY = $offset;

        return $this;
    }

    /**
     * Set output path for the processed image
     */
    public function outputPath(string $path): self
    {
        $this->outputPath = $path;

        return $this;
    }

    /**
     * Set custom colormap for image processing
     *
     * @param  array<string>  $colors  Array of hex color codes
     */
    public function colormap(array $colors): self
    {
        $this->colormap = $colors;

        return $this;
    }

    /**
     * Configure stage from model
     */
    public function configureFromModel(Model $model): self
    {
        $data = $model->getData();

        if ($this->width === null) {
            $this->width = $data->width > 0 ? $data->width : self::DEFAULT_WIDTH;
        }
        if ($this->height === null) {
            $this->height = $data->height;
        }
        if ($this->colors === null) {
            $this->colors = $data->colors;
        }
        if ($this->bitDepth === null) {
            $this->bitDepth = $data->bitDepth;
        }
        if ($this->rotation === null) {
            $this->rotation = $data->rotation;
        }
        if ($this->offsetX === null) {
            $this->offsetX = $data->offsetX;
        }
        if ($this->offsetY === null) {
            $this->offsetY = $data->offsetY;
        }
        if ($this->format === null) {
            $this->format = $this->getFormatFromMimeType($data->mimeType);
        }

        return $this;
    }

    /**
     * Process the payload through this stage
     *
     * @param  mixed  $payload  The payload to process (image path or array with image path)
     * @return string The path to the processed image
     *
     * @throws ProcessingException
     */
    public function __invoke(mixed $payload): string
    {
        $imagePath = $this->extractImagePath($payload);

        if (! file_exists($imagePath)) {
            throw new ProcessingException('Invalid or missing image file: '.$imagePath);
        }

        if (TrmnlPipeline::isFake()) {
            return $this->createMockProcessedImage($imagePath);
        }

        try {
            $imagick = new Imagick($imagePath);
            $this->applyTransformations($imagick);

            return $this->writeImage($imagePath, $imagick);
        } catch (ImagickException $e) {
            throw new ProcessingException(
                'Image processing failed: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Extract image path from payload
     */
    private function extractImagePath(mixed $payload): string
    {
        // If payload is a string, assume it's an image path
        if (is_string($payload)) {
            return $payload;
        }

        // If payload is an array with image_path key
        if (is_array($payload) && isset($payload['image_path'])) {
            return (string) $payload['image_path'];
        }

        return '';
    }

    /**
     * Apply all image transformations
     *
     * @throws ImagickException
     */
    private function applyTransformations(Imagick $imagick): void
    {

        // Resize image if dimensions are set (either explicitly or from model)
        $this->resize($imagick);

        // Apply offset (translate image) if specified
        $this->offset($imagick);

        // Rotate image if rotation is specified
        $this->rotate($imagick);

        // $this->transformColorSpace($imagick);

        // Apply colormap if needed
        $this->applyColormap($imagick);

        // Quantize colors if specified
        $this->quantize($imagick);

        // Set bit depth if specified (after quantization)
        $imagick->setImageDepth($this->bitDepth ?? self::DEFAULT_BIT_DEPTH);

        // Set output format if specified
        $format = $this->format ?? self::DEFAULT_FORMAT;
        if ($format === 'bmp') {
            // BMP3 needs to be set explicitly
            $imagick->setFormat('BMP3');
        } else {
            $imagick->setImageFormat(strtoupper($this->getFormatFromMimeType($format)));
        }

        // Strip image metadata for smaller file size
        $imagick->stripImage();
    }

    /**
     * Generate output path based on input path and format
     */
    private function generateOutputPath(string $inputPath): string
    {
        $pathInfo = pathinfo($inputPath);
        $extension = $this->format ?: ($pathInfo['extension'] ?? self::DEFAULT_FORMAT);
        $dirname = $pathInfo['dirname'] ?? '.';

        return $dirname.'/'.$pathInfo['filename'].'_processed.'.$extension;
    }

    /**
     * Create a canvas with exact dimensions and center the image on it
     *
     * @throws ImagickException
     */
    private function createCanvasWithCenteredImage(Imagick $imagick, int $canvasWidth, int $canvasHeight): void
    {
        $imageWidth = $imagick->getImageWidth();
        $imageHeight = $imagick->getImageHeight();

        // If the image is already the exact size, no need to create a canvas
        if ($imageWidth === $canvasWidth && $imageHeight === $canvasHeight) {
            return;
        }

        // Create a new canvas with the exact dimensions and white background
        $canvas = new Imagick;
        $canvas->newImage($canvasWidth, $canvasHeight, new ImagickPixel('white'));
        $canvas->setImageFormat($imagick->getImageFormat());

        // Calculate the position to center the image
        $x = (int) round(($canvasWidth - $imageWidth) / 2);
        $y = (int) round(($canvasHeight - $imageHeight) / 2);

        // Composite the resized image onto the canvas
        $canvas->compositeImage($imagick, Imagick::COMPOSITE_OVER, $x, $y);

        // Replace the original image with the canvas
        $imagick->clear();
        $imagick->readImageBlob($canvas->getImageBlob());

        $canvas->clear();
    }

    /**
     * Apply offset translation to the image
     *
     * @throws ImagickException
     */
    private function offset(Imagick $imagick): void
    {
        if ($this->offsetX === 0 && $this->offsetY === 0) {
            return;
        }

        // Create a new canvas with the same dimensions and white background
        $canvas = new Imagick;
        $canvas->newImage($imagick->getImageWidth(), $imagick->getImageHeight(), new ImagickPixel('white'));
        $canvas->setImageFormat($imagick->getImageFormat());

        // Composite the image onto the canvas at the offset position
        $canvas->compositeImage($imagick, Imagick::COMPOSITE_OVER, $this->offsetX ?? self::DEFAULT_OFFSET_X, $this->offsetY ?? self::DEFAULT_OFFSET_Y);

        // Replace the original image with the canvas
        $imagick->clear();
        $imagick->readImageBlob($canvas->getImageBlob());

        $canvas->clear();
    }

    /**
     * Get format from MIME type
     */
    private function getFormatFromMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/bmp', 'bmp' => 'bmp',
            default => self::DEFAULT_FORMAT,
        };
    }

    /**
     * @throws ImagickException
     */
    public function resize(Imagick $imagick): void
    {
        $originalWidth = $imagick->getImageWidth();
        $originalHeight = $imagick->getImageHeight();

        /** @var int $targetWidth */
        $targetWidth = $this->width ?? self::DEFAULT_WIDTH;
        /** @var int $targetHeight */
        $targetHeight = $this->height ?? self::DEFAULT_HEIGHT;

        // Resize the image to fit within the target dimensions while preserving aspect ratio
        if ($targetWidth !== $originalWidth || $targetHeight !== $originalHeight) {
            $imagick->resizeImage(
                $targetWidth,
                $targetHeight,
                Imagick::FILTER_LANCZOS,
                1,
                true // Use bestfit to preserve aspect ratio
            );
        }

        // Create a canvas with exact dimensions and center the image
        $this->createCanvasWithCenteredImage(
            $imagick,
            $this->width ?? self::DEFAULT_WIDTH,
            $this->height ?? self::DEFAULT_HEIGHT
        );
    }

    /**
     * @throws ImagickException
     */
    public function rotate(Imagick $imagick): void
    {
        if ($this->rotation !== 0) {
            $imagick->rotateImage(new ImagickPixel('white'), (float) ($this->rotation ?? self::DEFAULT_ROTATION));
        }
    }

    /**
     * @throws ImagickException
     */
    public function transformColorSpace(Imagick $imagick): void
    {
        $imagick->transformImageColorspace(Imagick::COLORSPACE_GRAY);
    }

    /**
     * @throws ImagickException
     */
    public function writeImage(string $imagePath, Imagick $imagick): string
    {
        $outputPath = $this->outputPath ?? $this->generateOutputPath($imagePath);
        $imagick->writeImage($outputPath);
        $imagick->clear();

        return $outputPath;
    }

    /**
     * @throws ImagickException
     */
    public function quantize(Imagick $imagick): void
    {
        $imagick->setOption('dither', 'FloydSteinberg');
        $imagick->quantizeImage(
            $this->colors ?? self::DEFAULT_COLORS,
            Imagick::COLORSPACE_GRAY,
            0,
            true,
            false
        );
    }

    /**
     * Apply colormap to the image
     *
     * @throws ImagickException
     */
    public function applyColormap(Imagick $imagick): void
    {
        $format = $this->format ?? self::DEFAULT_FORMAT;
        $bitDepth = $this->bitDepth ?? self::DEFAULT_BIT_DEPTH;

        // Only apply colormap for PNG format with <= 2-bit depth
        // TODO: support other bit depths
        if ($format !== 'png' || $bitDepth > 2) {
            return;
        }

        // Determine colors: prefer explicit colormap, otherwise choose by bit depth
        $colors = $this->colormap
            ?? ($bitDepth == 2
                ? $this->getDefault2BitColormap()
                : ['#000000', '#ffffff']);

        // Apply colormap using native Imagick functions
        $this->setImageColormap($imagick, $colors);
    }

    /**
     * Get default 2-bit grayscale colormap
     *
     * @return array<string>
     */
    private function getDefault2BitColormap(): array
    {
        return [
            '#000000', // Black
            '#555555', // Dark gray
            '#aaaaaa', // Light gray
            '#ffffff', // White
        ];
    }

    /**
     * Set colormap on image using native Imagick functions
     *
     * @param  array<string>  $colors  Array of hex color codes
     *
     * @throws ImagickException
     * @throws ImagickPixelException
     * @throws ImagickDrawException
     */
    private function setImageColormap(Imagick $imagick, array $colors): void
    {
        $paletteImage = new Imagick;
        $paletteImage->newImage(count($colors), 1, 'white');
        $paletteImage->setImageFormat('png');

        $counter = count($colors);
        for ($i = 0; $i < $counter; $i++) {
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel($colors[$i]));
            $draw->point($i, 0);
            $paletteImage->drawImage($draw);
        }
        $paletteImage->setImageType(Imagick::IMGTYPE_PALETTE);

        $imagick->remapImage($paletteImage, Imagick::DITHERMETHOD_FLOYDSTEINBERG);
    }

    /**
     * Create a mock processed image file for testing
     */
    private function createMockProcessedImage(string $inputPath): string
    {
        $outputPath = $this->outputPath ?? $this->generateOutputPath($inputPath);

        // Create a simple image file based on the target format
        $format = $this->format ?? self::DEFAULT_FORMAT;
        $width = $this->width ?? self::DEFAULT_WIDTH;
        $height = $this->height ?? self::DEFAULT_HEIGHT;

        $image = imagecreate($width, $height);
        if ($image === false) {
            throw new ProcessingException('Failed to create mock processed image');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        if ($white === false) {
            throw new ProcessingException('Failed to allocate color for mock processed image');
        }

        imagefill($image, 0, 0, $white);

        // Save in the appropriate format
        match ($format) {
            'png' => imagepng($image, $outputPath),
            'bmp' => imagebmp($image, $outputPath),
            default => imagepng($image, $outputPath),
        };

        imagedestroy($image);

        return $outputPath;
    }
}
