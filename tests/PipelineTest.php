<?php

declare(strict_types=1);

use Bnussbau\TrmnlPipeline\Exceptions\ProcessingException;
use Bnussbau\TrmnlPipeline\Model;
use Bnussbau\TrmnlPipeline\StageInterface;
use Bnussbau\TrmnlPipeline\Stages\BrowserStage;
use Bnussbau\TrmnlPipeline\Stages\ImageStage;
use Bnussbau\TrmnlPipeline\TrmnlPipeline;

describe('Pipeline', function (): void {
    it('can be instantiated', function (): void {
        $pipeline = new TrmnlPipeline;

        expect($pipeline)->toBeInstanceOf(TrmnlPipeline::class);
        expect($pipeline->getModel())->toBeNull();
    });

    it('can set and get model', function (): void {
        $pipeline = new TrmnlPipeline;
        $model = Model::OG_PNG;

        $pipeline->model($model);

        expect($pipeline->getModel())->toBe($model);
    });

    it('can chain model setting', function (): void {
        $pipeline = new TrmnlPipeline;
        $model = Model::OG_PNG;

        $result = $pipeline->model($model);

        expect($result)->toBe($pipeline);
    });

    it('can add stages to pipeline', function (): void {
        $pipeline = new TrmnlPipeline;
        $stage = new class implements StageInterface
        {
            public function __invoke(mixed $payload): mixed
            {
                return $payload;
            }
        };

        $result = $pipeline->pipe($stage);

        expect($result)->toBe($pipeline);
    });

    it('can process payload through pipeline', function (): void {
        $pipeline = new TrmnlPipeline;
        $stage = new class implements StageInterface
        {
            public function __invoke(mixed $payload): mixed
            {
                return ($payload ?? 5) * 2;
            }
        };

        $pipeline->pipe($stage);

        $result = $pipeline->process();

        expect($result)->toBe(10);
    });

    it('can process payload through multiple stages', function (): void {
        $pipeline = new TrmnlPipeline;

        $stage1 = new class implements StageInterface
        {
            public function __invoke(mixed $payload): mixed
            {
                return ($payload ?? 5) * 2;
            }
        };

        $stage2 = new class implements StageInterface
        {
            public function __invoke(mixed $payload): mixed
            {
                return $payload + 1;
            }
        };

        $pipeline->pipe($stage1)->pipe($stage2);

        $result = $pipeline->process();

        expect($result)->toBe(11); // (5 * 2) + 1
    });

    it('throws ProcessingException when stage fails', function (): void {
        $pipeline = new TrmnlPipeline;
        $stage = new class implements StageInterface
        {
            public function __invoke(mixed $payload): mixed
            {
                throw new \Exception('Stage failed');
            }
        };

        $pipeline->pipe($stage);

        expect(fn (): mixed => $pipeline->process())
            ->toThrow(ProcessingException::class, 'Pipeline processing failed: Stage failed');
    });

    it('can chain pipe calls', function (): void {
        $pipeline = new TrmnlPipeline;
        $stage1 = new class implements StageInterface
        {
            public function __invoke(mixed $payload): mixed
            {
                return $payload;
            }
        };
        $stage2 = new class implements StageInterface
        {
            public function __invoke(mixed $payload): mixed
            {
                return $payload;
            }
        };

        $result = $pipeline->pipe($stage1)->pipe($stage2);

        expect($result)->toBe($pipeline);
    });

    it('automatically configures stages with model when model is set', function (): void {
        $pipeline = new TrmnlPipeline;
        $model = Model::OG_BMP;
        $imageStage = new ImageStage;

        // Set model and add stage
        $pipeline->model($model)->pipe($imageStage);

        // The stage should be automatically configured with the model
        // We can verify this by checking that the stage has the correct format
        expect($imageStage->getFormat())->toBe('bmp');
    });

    it('does not configure stages when no model is set', function (): void {
        $pipeline = new TrmnlPipeline;
        $imageStage = new ImageStage;

        // Add stage without setting model
        $pipeline->pipe($imageStage);

        // The stage should not be configured (format should be null)
        expect($imageStage->getFormat())->toBeNull();
    });

    it('processes color palette end-to-end with inky_impression_13_3 model', function (): void {
        $pipeline = new TrmnlPipeline;
        $model = Model::INKY_IMPRESSION_13_3; // Has color-6a palette

        // Verify model has color palette
        expect($model->getPaletteIds())->toContain('color-6a');

        // Load HTML file with color palette colors
        $htmlPath = __DIR__.'/assets/color_6a_test.html';
        expect(file_exists($htmlPath))->toBeTrue();

        $htmlContent = file_get_contents($htmlPath);
        expect($htmlContent)->toBeString();

        // Create pipeline with BrowserStage and ImageStage
        $browserStage = new BrowserStage;
        $browserStage->html($htmlContent);

        $imageStage = new ImageStage;

        // Process through pipeline
        $pipeline
            ->model($model)
            ->pipe($browserStage)
            ->pipe($imageStage);

        $result = $pipeline->process();

        expect($result)->toBeString();
        expect(file_exists($result))->toBeTrue();

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    });
});
