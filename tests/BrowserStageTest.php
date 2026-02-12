<?php

declare(strict_types=1);

use Bnussbau\TrmnlPipeline\Exceptions\ProcessingException;
use Bnussbau\TrmnlPipeline\Model;
use Bnussbau\TrmnlPipeline\Stages\BrowserStage;
use Spatie\Browsershot\Browsershot;

describe('BrowserStage', function (): void {
    it('can be instantiated', function (): void {
        $stage = new BrowserStage;

        expect($stage)->toBeInstanceOf(BrowserStage::class);
    });

    it('can be instantiated with custom Browsershot instance', function (): void {
        $browsershot = Browsershot::html('<html><body>Test</body></html>');
        $stage = new BrowserStage($browsershot);

        expect($stage)->toBeInstanceOf(BrowserStage::class);
    });

    it('uses default Browsershot when none provided', function (): void {
        $stage = new BrowserStage;

        expect($stage)->toBeInstanceOf(BrowserStage::class);
    });

    it('accepts custom Browsershot instance in constructor', function (): void {
        // Create a real Browsershot instance to test constructor acceptance
        $browsershot = Browsershot::html('<html><body>Test</body></html>');
        $stage = new BrowserStage($browsershot);

        expect($stage)->toBeInstanceOf(BrowserStage::class);

        // Test that the stage can still be configured
        $result = $stage->html('<html><body>Test</body></html>');
        expect($result)->toBe($stage);
    });

    it('can set HTML content', function (): void {
        $stage = new BrowserStage;

        $result = $stage->html('<html><body>Test</body></html>');

        expect($result)->toBe($stage);
    });

    it('can set width and height', function (): void {
        $stage = new BrowserStage;

        $result = $stage->width(1024)->height(768);

        expect($result)->toBe($stage);
    });

    it('can set Browsershot options', function (): void {
        $stage = new BrowserStage;

        $result = $stage->setBrowsershotOption('timeout', 30);

        expect($result)->toBe($stage);
    });

    it('can set URL', function (): void {
        $stage = new BrowserStage;

        $result = $stage->url('https://example.com');

        expect($result)->toBe($stage);
    });

    it('throws exception when no HTML content or URL is provided', function (): void {
        $stage = new BrowserStage;

        expect(fn (): string => $stage(null))
            ->toThrow(ProcessingException::class, 'No HTML content or URL provided for browser rendering. Use html() or url() method to set content or URL.');
    });

    it('throws exception when both HTML and URL are provided', function (): void {
        $stage = new BrowserStage;
        $stage->html('<html><body>Test</body></html>')->url('https://example.com');

        expect(fn (): string => $stage(null))
            ->toThrow(ProcessingException::class, 'Provide either HTML content or a URL, not both. Use html() or url().');
    });

    it('can process URL set on stage', function (): void {
        $browsershot = new class extends Browsershot
        {
            public function setUrl(string $url): static
            {
                return $this;
            }

            public function windowSize(int $width, int $height): static
            {
                return $this;
            }

            public function setScreenshotType(string $type, ?int $quality = null): static
            {
                return $this;
            }

            public function setOption($key, $value): static
            {
                return $this;
            }

            public function save(string $targetPath): void
            {
                $img = imagecreate(1, 1);
                if ($img === false) {
                    return;
                }
                $white = imagecolorallocate($img, 255, 255, 255);
                if ($white !== false) {
                    imagefill($img, 0, 0, $white);
                }
                imagepng($img, $targetPath);
                imagedestroy($img);
            }
        };
        $stage = new BrowserStage($browsershot);
        $stage->url('https://example.com');

        $result = $stage(null);
        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can process HTML set on stage', function (): void {
        $stage = new BrowserStage;
        $stage->html('<html><body><h1>Test</h1></body></html>');

        // Test that the method returns a string (path to generated image)
        $result = $stage(null);
        expect($result)->toBeString();

        // Clean up if file was created
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can process with custom width and height', function (): void {
        $stage = new BrowserStage;
        $stage
            ->html('<html><body><h1>Test</h1></body></html>')
            ->width(1024)
            ->height(768);

        // Test that the method returns a string (path to generated image)
        $result = $stage(null);
        expect($result)->toBeString();

        // Clean up if file was created
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can process with Browsershot options', function (): void {
        $stage = new BrowserStage;
        $stage
            ->html('<html><body><h1>Test</h1></body></html>')
            ->setBrowsershotOption('timeout', 30);

        // Test that the method returns a string (path to generated image)
        $result = $stage(null);
        expect($result)->toBeString();

        // Clean up if file was created
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can use default dimensions', function (): void {
        $stage = new BrowserStage;
        $result = $stage->useDefaultDimensions();

        expect($result)->toBe($stage);
    });

    it('can configure from model', function (): void {
        $stage = new BrowserStage;
        $model = Model::OG_PNG;

        $result = $stage->configureFromModel($model);

        expect($result)->toBe($stage);
    });

    it('configures dimensions from model when not using default dimensions', function (): void {
        $stage = new BrowserStage;
        $model = Model::OG_PNG;

        // Get model dimensions
        $modelWidth = $model->getWidth();
        $modelHeight = $model->getHeight();

        // Configure from model
        $stage->configureFromModel($model);

        // Create a reflection to access private properties for testing
        $reflection = new ReflectionClass($stage);
        $widthProperty = $reflection->getProperty('width');
        $heightProperty = $reflection->getProperty('height');
        $widthProperty->setAccessible(true);
        $heightProperty->setAccessible(true);

        expect($widthProperty->getValue($stage))->toBe($modelWidth);
        expect($heightProperty->getValue($stage))->toBe($modelHeight);
    });

    it('ignores model dimensions when useDefaultDimensions is called', function (): void {
        $stage = new BrowserStage;
        $model = Model::OG_PNG;

        // Set useDefaultDimensions first
        $stage->useDefaultDimensions();

        // Configure from model
        $stage->configureFromModel($model);

        // Create a reflection to access private properties for testing
        $reflection = new ReflectionClass($stage);
        $widthProperty = $reflection->getProperty('width');
        $heightProperty = $reflection->getProperty('height');
        $widthProperty->setAccessible(true);
        $heightProperty->setAccessible(true);

        // Should still be default dimensions (800x480)
        expect($widthProperty->getValue($stage))->toBe(800);
        expect($heightProperty->getValue($stage))->toBe(480);
    });

    it('can process with model-configured dimensions', function (): void {
        $stage = new BrowserStage;
        $model = Model::OG_PNG;

        $stage
            ->html('<html><body><h1>Test</h1></body></html>')
            ->configureFromModel($model);

        // Test that the method returns a string (path to generated image)
        $result = $stage(null);
        expect($result)->toBeString();

        // Clean up if file was created
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can process with useDefaultDimensions ignoring model', function (): void {
        $stage = new BrowserStage;
        $model = Model::OG_PNG;

        $stage
            ->html('<html><body><h1>Test</h1></body></html>')
            ->useDefaultDimensions()
            ->configureFromModel($model);

        // Test that the method returns a string (path to generated image)
        $result = $stage(null);
        expect($result)->toBeString();

        // Clean up if file was created
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('throws ProcessingException when Browsershot fails', function (): void {
        $stage = new BrowserStage;

        // Use a very short timeout to trigger a failure
        $stage
            ->html('<html><body><h1>Test</h1></body></html>')
            ->setBrowsershotOption('timeout', 1); // 1ms timeout should cause failure

        expect(fn (): string => $stage(null))
            ->toThrow(ProcessingException::class, 'Browser rendering failed:');
    });
});
