<?php

declare(strict_types=1);

use Bnussbau\EpaperPipeline\EpaperPipeline;
use Bnussbau\EpaperPipeline\Model;
use Bnussbau\EpaperPipeline\Stages\BrowserStage;
use Bnussbau\EpaperPipeline\Stages\ImageStage;

afterEach(function (): void {
    // Clean up after each test
    EpaperPipeline::restore();
});

it('can enable and disable fake mode', function (): void {
    expect(EpaperPipeline::isFake())->toBeFalse();

    EpaperPipeline::fake();
    expect(EpaperPipeline::isFake())->toBeTrue();

    EpaperPipeline::restore();
    expect(EpaperPipeline::isFake())->toBeFalse();
});

it('returns mock image path when browser stage is in fake mode', function (): void {
    EpaperPipeline::fake();

    $browserStage = new BrowserStage;
    $browserStage->html('<html><body>Test</body></html>');

    $result = $browserStage(null);

    expect($result)->toBeString();
    expect(file_exists($result))->toBeTrue();
    expect(str_contains($result, 'browsershot_fake_'))->toBeTrue();
    expect(str_ends_with($result, '.png'))->toBeTrue();
});

it('returns mock processed image path when image stage is in fake mode', function (): void {
    EpaperPipeline::fake();

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
    EpaperPipeline::fake();

    $html = '<html><body>Test Content</body></html>';

    $result = (new EpaperPipeline)
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
    EpaperPipeline::fake();

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
    EpaperPipeline::fake();

    $browserStage = new BrowserStage;

    expect(fn (): string => $browserStage(null))
        ->toThrow(Exception::class, 'No HTML content or URL provided for browser rendering');
});

it('still validates file existence in fake mode', function (): void {
    EpaperPipeline::fake();

    $imageStage = new ImageStage;

    expect(fn (): string => $imageStage('/nonexistent/file.png'))
        ->toThrow(Exception::class, 'Invalid or missing image file');
});
