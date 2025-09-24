<?php

declare(strict_types=1);

namespace Bnussbau\TrmnlPipeline\Stages;

use Bnussbau\TrmnlPipeline\Exceptions\ProcessingException;
use Bnussbau\TrmnlPipeline\Model;
use Bnussbau\TrmnlPipeline\StageInterface;
use Bnussbau\TrmnlPipeline\TrmnlPipeline;
use Spatie\Browsershot\Browsershot;

/**
 * Browser stage for HTML to image rendering
 */
class BrowserStage implements StageInterface
{
    /**
     * Default fallback values for browser dimensions
     */
    private const DEFAULT_WIDTH = 800;

    private const DEFAULT_HEIGHT = 480;

    private ?string $html = null;

    private ?int $width = null;

    private ?int $height = null;

    private bool $useDefaultDimensions = false;

    /** @var array<string, mixed> */
    private array $browsershotOptions = [];

    public function __construct(private readonly ?Browsershot $browsershotInstance = null) {}

    /**
     * Set HTML content to render
     */
    public function html(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Set browser viewport width
     */
    public function width(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Set browser viewport height
     */
    public function height(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Configure Browsershot options
     */
    public function setBrowsershotOption(string $name, mixed $value): self
    {
        $this->browsershotOptions[$name] = $value;

        return $this;
    }

    /**
     * Use default dimensions instead of model dimensions
     */
    public function useDefaultDimensions(): self
    {
        $this->useDefaultDimensions = true;

        return $this;
    }

    /**
     * Configure the stage from a model
     */
    public function configureFromModel(Model $model): self
    {
        // Only set dimensions if they haven't been explicitly set by the user
        // and we're not using default dimensions
        if (! $this->useDefaultDimensions) {
            if ($this->width === null) {
                $this->width = $model->getWidth();
            }
            if ($this->height === null) {
                $this->height = $model->getHeight();
            }
        } else {
            // When using default dimensions, set them if not already set
            if ($this->width === null) {
                $this->width = self::DEFAULT_WIDTH;
            }
            if ($this->height === null) {
                $this->height = self::DEFAULT_HEIGHT;
            }
        }

        return $this;
    }

    /**
     * Process the payload through this stage
     *
     * @param  mixed  $payload  The payload to process (ignored, HTML should be set on stage)
     * @return string The path to the generated PNG image
     *
     * @throws ProcessingException
     */
    public function __invoke(mixed $payload): string
    {
        if ($this->html === null || $this->html === '') {
            throw new ProcessingException('No HTML content provided for browser rendering. Use html() method to set HTML content.');
        }

        if (TrmnlPipeline::isFake()) {
            return $this->createMockImage();
        }

        try {
            // Create temporary file for output
            $tempFile = tempnam(sys_get_temp_dir(), 'browsershot_').'.png';

            // Configure Browsershot - use provided instance or create default
            if ($this->browsershotInstance instanceof \Spatie\Browsershot\Browsershot) {
                // Clone the provided instance and set HTML
                $browsershot = clone $this->browsershotInstance;
                $browsershot = $browsershot->html($this->html);
            } else {
                // Create default Browsershot instance
                $browsershot = Browsershot::html($this->html);
            }

            $browsershot = $browsershot
                ->windowSize($this->width ?? self::DEFAULT_WIDTH, $this->height ?? self::DEFAULT_HEIGHT)
                ->setScreenshotType('png');

            // Apply custom options
            foreach ($this->browsershotOptions as $name => $value) {
                $browsershot = $browsershot->setOption($name, $value);
            }

            // Generate the image
            $browsershot->save($tempFile);

            if (! file_exists($tempFile)) {
                throw new ProcessingException('Failed to generate browser screenshot');
            }

            return $tempFile;
        } catch (\Exception $e) {
            throw new ProcessingException(
                'Browser rendering failed: '.$e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Create a mock image file for testing
     */
    private function createMockImage(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'browsershot_fake_').'.png';

        $image = imagecreate(800, 480);
        if ($image === false) {
            throw new ProcessingException('Failed to create mock image');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        if ($white === false) {
            throw new ProcessingException('Failed to allocate color for mock image');
        }

        imagefill($image, 0, 0, $white);
        imagepng($image, $tempFile);
        imagedestroy($image);

        return $tempFile;
    }
}
