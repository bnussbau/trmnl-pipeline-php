<?php

declare(strict_types=1);

use Bnussbau\TrmnlPipeline\Exceptions\ProcessingException;
use Bnussbau\TrmnlPipeline\Model;
use Bnussbau\TrmnlPipeline\Stages\ImageStage;

describe('ImageStage', function (): void {
    beforeEach(function (): void {
        // Create a test image for processing
        $this->testImagePath = sys_get_temp_dir().'/test_image.png';
        $imagick = new Imagick;
        $imagick->newImage(100, 100, new ImagickPixel('white'));
        $imagick->setImageFormat('png');
        $imagick->writeImage($this->testImagePath);
        $imagick->clear();
    });

    afterEach(function (): void {
        // Clean up test image
        if (file_exists($this->testImagePath)) {
            unlink($this->testImagePath);
        }
    });

    it('can be instantiated', function (): void {
        $stage = new ImageStage;

        expect($stage)->toBeInstanceOf(ImageStage::class);
    });

    it('can set format', function (): void {
        $stage = new ImageStage;

        $result = $stage->format('bmp');

        expect($result)->toBe($stage);
    });

    it('can set width and height', function (): void {
        $stage = new ImageStage;

        $result = $stage->width(800)->height(600);

        expect($result)->toBe($stage);
    });

    it('can set colors and bit depth', function (): void {
        $stage = new ImageStage;

        $result = $stage->colors(2)->bitDepth(1);

        expect($result)->toBe($stage);
    });

    it('can set rotation', function (): void {
        $stage = new ImageStage;

        $result = $stage->rotation(90);

        expect($result)->toBe($stage);
    });

    it('can set offset X and Y', function (): void {
        $stage = new ImageStage;

        $result = $stage->offsetX(10)->offsetY(20);

        expect($result)->toBe($stage);
    });

    it('can set output path', function (): void {
        $stage = new ImageStage;

        $result = $stage->outputPath('/tmp/output.png');

        expect($result)->toBe($stage);
    });

    it('can configure from model', function (): void {
        $stage = new ImageStage;
        $model = Model::OG_PNG;

        $result = $stage->configureFromModel($model);

        expect($result)->toBe($stage);
    });

    it('can process image from string path', function (): void {
        $stage = new ImageStage;
        $stage->width(50)->height(50);

        $result = $stage($this->testImagePath);

        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can process image from array payload', function (): void {
        $stage = new ImageStage;
        $stage->width(50)->height(50);

        $payload = ['image_path' => $this->testImagePath];
        $result = $stage($payload);

        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('throws exception for invalid image path', function (): void {
        $stage = new ImageStage;

        expect(fn (): string => $stage('/invalid/path.png'))
            ->toThrow(ProcessingException::class, 'Invalid or missing image file: /invalid/path.png');
    });

    it('throws exception for empty payload', function (): void {
        $stage = new ImageStage;

        expect(fn (): string => $stage(''))
            ->toThrow(ProcessingException::class, 'Invalid or missing image file: ');
    });

    it('can process with custom output path', function (): void {
        $stage = new ImageStage;
        $outputPath = sys_get_temp_dir().'/custom_output.png';
        $stage->outputPath($outputPath);

        $result = $stage($this->testImagePath);

        expect($result)->toBe($outputPath);
        expect(file_exists($outputPath))->toBeTrue();

        // Clean up
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
    });

    it('can process with model configuration', function (): void {
        $stage = new ImageStage;
        $model = Model::OG_PNG;
        $stage->configureFromModel($model);

        $result = $stage($this->testImagePath);

        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('loads offset values from model configuration', function (): void {
        $stage = new ImageStage;
        $model = Model::AMAZON_KINDLE_2024; // This model has offset_x: 75, offset_y: 25
        $stage->configureFromModel($model);

        $result = $stage($this->testImagePath);
        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();
        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can process image with only width set (maintains aspect ratio)', function (): void {
        $stage = new ImageStage;
        $stage->width(50); // Only width set, height should be calculated

        $result = $stage($this->testImagePath);

        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can process image with only height set (maintains aspect ratio)', function (): void {
        $stage = new ImageStage;
        $stage->height(50); // Only height set, width should be calculated

        $result = $stage($this->testImagePath);

        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can process image with both width and height set (uses bestfit)', function (): void {
        $stage = new ImageStage;
        $stage->width(200)->height(50); // Both set, should use bestfit to preserve aspect ratio

        $result = $stage($this->testImagePath);

        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can process image with rotation', function (): void {
        $stage = new ImageStage;
        $stage->rotation(90);

        $result = $stage($this->testImagePath);

        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can process image with offset', function (): void {
        $stage = new ImageStage;
        $stage->offsetX(10)->offsetY(20);

        $result = $stage($this->testImagePath);

        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('applies offset translation', function (): void {
        // Create a test image with a black square in the top-left corner
        $testImagePath = sys_get_temp_dir().'/offset_test.png';
        $imagick = new Imagick;
        $imagick->newImage(800, 480, new ImagickPixel('white'));
        $imagick->setImageFormat('png');

        // Draw a black square in the top-left corner
        $draw = new ImagickDraw;
        $draw->setFillColor('black');
        $draw->rectangle(0, 0, 20, 20);
        $imagick->drawImage($draw);
        $imagick->writeImage($testImagePath);
        $imagick->clear();

        // Process with offset
        $stage = new ImageStage;
        $stage->offsetX(30)->offsetY(30);
        $result = $stage($testImagePath);

        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();

        // The black square should now be at position (30, 30)
        $resultImage = new Imagick($result);
        $pixel = $resultImage->getImagePixelColor(30, 30);
        $colors = $pixel->getColor();
        // Should be black at the offset position
        expect($colors['r'])->toBe(0);
        expect($colors['g'])->toBe(0);
        expect($colors['b'])->toBe(0);

        // Clean up
        $resultImage->clear();
        if (file_exists($testImagePath)) {
            unlink($testImagePath);
        }
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can process image with colors and bit depth', function (): void {
        $stage = new ImageStage;
        $stage->colors(2)->bitDepth(1);

        $result = $stage($this->testImagePath);

        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can process image with format conversion', function (): void {
        $stage = new ImageStage;
        $stage->format('bmp');

        $result = $stage($this->testImagePath);

        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    });

    it('can get format', function (): void {
        $stage = new ImageStage;
        $stage->format('bmp');

        expect($stage->getFormat())->toBe('bmp');
    });

    it('handles payload with zero string', function (): void {
        $stage = new ImageStage;

        expect(fn (): string => $stage('0'))
            ->toThrow(ProcessingException::class, 'Invalid or missing image file: 0');
    });

    it('handles invalid array payload', function (): void {
        $stage = new ImageStage;

        expect(fn (): string => $stage(['invalid_key' => 'value']))
            ->toThrow(ProcessingException::class, 'Invalid or missing image file: ');
    });

    it('handles ImagickException during image processing', function (): void {
        $stage = new ImageStage;

        // Create a corrupted image file that will cause ImagickException
        $corruptedImagePath = sys_get_temp_dir().'/corrupted_image.png';
        file_put_contents($corruptedImagePath, 'This is not a valid image file content');

        expect(fn (): string => $stage($corruptedImagePath))
            ->toThrow(ProcessingException::class, 'Image processing failed:');

        // Clean up
        if (file_exists($corruptedImagePath)) {
            unlink($corruptedImagePath);
        }
    });
});
