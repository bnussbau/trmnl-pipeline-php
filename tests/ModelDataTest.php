<?php

declare(strict_types=1);

use Bnussbau\TrmnlPipeline\Data\ModelData;
use Bnussbau\TrmnlPipeline\Exceptions\ProcessingException;

describe('ModelData', function (): void {
    it('can load models from JSON', function (): void {
        $models = ModelData::loadFromJson();

        expect($models)->toBeArray();
        expect($models)->toHaveKey('og_png');
        expect($models['og_png'])->toBeInstanceOf(ModelData::class);
    });

    it('can get model by name', function (): void {
        $model = ModelData::getByName('og_png');

        expect($model)->toBeInstanceOf(ModelData::class);
        expect($model->name)->toBe('og_png');
        expect($model->label)->toBe('TRMNL OG (1-bit)');
        expect($model->width)->toBe(800);
        expect($model->height)->toBe(480);
    });

    it('can get models by kind', function (): void {
        $trmnlModels = ModelData::getByKind('trmnl');
        $kindleModels = ModelData::getByKind('kindle');
        $byodModels = ModelData::getByKind('byod');

        expect($trmnlModels)->toBeArray();
        expect($kindleModels)->toBeArray();
        expect($byodModels)->toBeArray();

        // Check that all returned models have the correct kind
        foreach ($trmnlModels as $model) {
            expect($model->kind)->toBe('trmnl');
        }
        foreach ($kindleModels as $model) {
            expect($model->kind)->toBe('kindle');
        }
        foreach ($byodModels as $model) {
            expect($model->kind)->toBe('byod');
        }
    });

    it('can get all model names', function (): void {
        $names = ModelData::getAllNames();

        expect($names)->toBeArray();
        expect($names)->toContain('og_png');
        expect($names)->toContain('amazon_kindle_2024');
        expect($names)->toContain('inkplate_10');
    });

    it('throws exception for invalid model name', function (): void {
        expect(fn (): ModelData => ModelData::getByName('invalid_model'))
            ->toThrow(ProcessingException::class, "Model 'invalid_model' not found in models data");
    });

    it('has correct properties for OG PNG model', function (): void {
        $model = ModelData::getByName('og_png');

        expect($model->name)->toBe('og_png');
        expect($model->label)->toBe('TRMNL OG (1-bit)');
        expect($model->description)->toBe('TRMNL OG (1-bit)');
        expect($model->width)->toBe(800);
        expect($model->height)->toBe(480);
        expect($model->colors)->toBe(2);
        expect($model->bitDepth)->toBe(1);
        expect($model->scaleFactor)->toBe(1.0);
        expect($model->rotation)->toBe(0);
        expect($model->mimeType)->toBe('image/png');
        expect($model->offsetX)->toBe(0);
        expect($model->offsetY)->toBe(0);
        expect($model->kind)->toBe('trmnl');
        expect($model->paletteIds)->toBe(['bw']);
    });

    it('has correct properties for Amazon Kindle 2024 model', function (): void {
        $model = ModelData::getByName('amazon_kindle_2024');

        expect($model)->toBeInstanceOf(ModelData::class);
        expect($model->name)->toBe('amazon_kindle_2024');
        expect($model->label)->toBe('Amazon Kindle 2024');
        expect($model->description)->toBe('Amazon Kindle 2024');
        expect($model->width)->toBe(1400);
        expect($model->height)->toBe(840);
        expect($model->colors)->toBe(256);
        expect($model->bitDepth)->toBe(8);
        expect($model->scaleFactor)->toBe(1.75);
        expect($model->rotation)->toBe(90);
        expect($model->mimeType)->toBe('image/png');
        expect($model->offsetX)->toBe(75);
        expect($model->offsetY)->toBe(25);
        expect($model->kind)->toBe('kindle');
        expect($model->paletteIds)->toBe(['gray-256']);
    });

    it('has correct palette IDs for models with color palettes', function (): void {
        $inkyImpression73 = ModelData::getByName('inky_impression_7_3');
        $inkyImpression133 = ModelData::getByName('inky_impression_13_3');

        expect($inkyImpression73->paletteIds)->toBe(['color-7a', 'color-6a', 'bw']);
        expect($inkyImpression133->paletteIds)->toBe(['color-6a', 'bw']);
    });

});
