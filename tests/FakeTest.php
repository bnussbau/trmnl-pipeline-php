<?php

declare(strict_types=1);

use Bnussbau\TrmnlPipeline\Model;
use Bnussbau\TrmnlPipeline\Stages\BrowserStage;
use Bnussbau\TrmnlPipeline\Stages\ImageStage;
use Bnussbau\TrmnlPipeline\TrmnlPipeline;

afterEach(function (): void {
    // Clean up after each test
    TrmnlPipeline::restore();
});

it('can enable and disable fake mode', function (): void {
    expect(TrmnlPipeline::isFake())->toBeFalse();

    TrmnlPipeline::fake();
    expect(TrmnlPipeline::isFake())->toBeTrue();

    TrmnlPipeline::restore();
    expect(TrmnlPipeline::isFake())->toBeFalse();
});

it('returns mock image path when browser stage is in fake mode', function (): void {
    TrmnlPipeline::fake();

    $browserStage = new BrowserStage;
    $browserStage->html('<html><body>Test</body></html>');

    $result = $browserStage(null);

    expect($result)->toBeString();
    expect(file_exists($result))->toBeTrue();
    expect(str_contains($result, 'browsershot_fake_'))->toBeTrue();
    expect(str_ends_with($result, '.png'))->toBeTrue();
});

it('returns mock processed image path when image stage is in fake mode', function (): void {
    TrmnlPipeline::fake();

    // Create a temporary input image
    $inputImage = tempnam(sys_get_temp_dir(), 'test_input_').'.png';
    $image = imagecreate(10, 10);
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);
    imagepng($image, $inputImage);
    imagedestroy($image);

    $imageStage = new ImageStage;
    $imageStage->format('png')->width(800)->height(480);

    $result = $imageStage($inputImage);

    expect($result)->toBeString();
    expect(file_exists($result))->toBeTrue();
    expect(str_contains($result, '_processed.png'))->toBeTrue();

    // Clean up
    unlink($inputImage);
    unlink($result);
});

it('processes full pipeline in fake mode', function (): void {
    TrmnlPipeline::fake();

    $html = '<html><body>Test Content</body></html>';

    $result = (new TrmnlPipeline)
        ->model(Model::OG)
        ->pipe((new BrowserStage)->html($html))
        ->pipe(new ImageStage)
        ->process();

    expect($result)->toBeString();
    expect(file_exists($result))->toBeTrue();
    expect(str_contains((string) $result, '_processed.png'))->toBeTrue();

    // Clean up
    unlink($result);
});

it('respects image stage configuration in fake mode', function (): void {
    TrmnlPipeline::fake();

    // Create a temporary input image
    $inputImage = tempnam(sys_get_temp_dir(), 'test_input_').'.png';
    $image = imagecreate(10, 10);
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);
    imagepng($image, $inputImage);
    imagedestroy($image);

    $imageStage = new ImageStage;
    $imageStage
        ->format('bmp')
        ->width(400)
        ->height(300)
        ->outputPath('/tmp/custom_output.bmp');

    $result = $imageStage($inputImage);

    expect($result)->toBe('/tmp/custom_output.bmp');
    expect(file_exists($result))->toBeTrue();

    // Clean up
    unlink($inputImage);
    unlink($result);
});

it('still validates input in fake mode', function (): void {
    TrmnlPipeline::fake();

    $browserStage = new BrowserStage;

    expect(fn (): string => $browserStage(null))
        ->toThrow(Exception::class, 'No HTML content provided for browser rendering');
});

it('still validates file existence in fake mode', function (): void {
    TrmnlPipeline::fake();

    $imageStage = new ImageStage;

    expect(fn (): string => $imageStage('/nonexistent/file.png'))
        ->toThrow(Exception::class, 'Invalid or missing image file');
});
