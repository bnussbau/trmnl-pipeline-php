<?php

declare(strict_types=1);

use Bnussbau\TrmnlPipeline\Data\ModelData;
use Bnussbau\TrmnlPipeline\Exceptions\ProcessingException;
use Bnussbau\TrmnlPipeline\Model;

describe('Model', function (): void {
    it('can get model data for OG PNG', function (): void {
        $model = Model::OG_PNG;
        $data = $model->getData();

        expect($data->name)->toBe('og_png')
            ->and($data->label)->toBe('TRMNL OG (1-bit)')
            ->and($data->width)->toBe(800)
            ->and($data->height)->toBe(480)
            ->and($data->colors)->toBe(2)
            ->and($data->bitDepth)->toBe(1)
            ->and($data->mimeType)->toBe('image/png')
            ->and($data->kind)->toBe('trmnl');
    });

    it('can get helper methods for model properties', function (): void {
        $model = Model::OG_PNG;

        expect($model->getLabel())->toBe('TRMNL OG (1-bit)')
            ->and($model->getDescription())->toBe('TRMNL OG (1-bit)')
            ->and($model->getWidth())->toBe(800)
            ->and($model->getHeight())->toBe(480)
            ->and($model->getColors())->toBe(2)
            ->and($model->getBitDepth())->toBe(1)
            ->and($model->getScaleFactor())->toBe(1.0)
            ->and($model->getRotation())->toBe(0)
            ->and($model->getMimeType())->toBe('image/png')
            ->and($model->getOffsetX())->toBe(0)
            ->and($model->getOffsetY())->toBe(0)
            ->and($model->getKind())->toBe('trmnl');
    });

    it('can get TRMNL models', function (): void {
        $trmnlModels = Model::getTrmnlModels();

        expect($trmnlModels[0])->toBeInstanceOf(Model::class);
        expect($trmnlModels[0]->getKind())->toBe('trmnl');
    });

    it('can get Kindle models', function (): void {
        $kindleModels = Model::getKindleModels();

        expect($kindleModels[0])->toBeInstanceOf(Model::class);
        expect($kindleModels[0]->getKind())->toBe('kindle');
    });

    it('can get BYOD models', function (): void {
        $byodModels = Model::getByodModels();

        expect($byodModels[0])->toBeInstanceOf(Model::class);
        expect($byodModels[0]->getKind())->toBe('byod');
    });

    it('throws exception for invalid model name', function (): void {
        expect(fn (): \Bnussbau\TrmnlPipeline\Data\ModelData => ModelData::getByName('invalid_model'))
            ->toThrow(ProcessingException::class, "Model 'invalid_model' not found in models data");
    });
});
