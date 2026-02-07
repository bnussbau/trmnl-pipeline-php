<?php

declare(strict_types=1);

use Bnussbau\TrmnlPipeline\Data\PaletteData;

describe('PaletteData', function (): void {
    it('can load palettes from JSON', function (): void {
        $palettes = PaletteData::loadFromJson();

        expect($palettes)->toBeArray();
        expect($palettes)->toHaveKey('bw');
        expect($palettes)->toHaveKey('color-6a');
        expect($palettes)->toHaveKey('color-7a');
        expect($palettes['bw'])->toBeInstanceOf(PaletteData::class);
    });

    it('can get palette by ID', function (): void {
        $palette = PaletteData::getById('bw');

        expect($palette)->toBeInstanceOf(PaletteData::class);
        expect($palette->id)->toBe('bw');
        expect($palette->name)->toBe('Black & White (1-bit)');
        expect($palette->grays)->toBe(2);
        expect($palette->colors)->toBeNull();
    });

    it('can get color palette by ID', function (): void {
        $palette = PaletteData::getById('color-6a');

        expect($palette)->toBeInstanceOf(PaletteData::class);
        expect($palette->id)->toBe('color-6a');
        expect($palette->name)->toBe('Color (6 colors)');
        expect($palette->grays)->toBe(2);
        expect($palette->colors)->toBe([
            '#FF0000',
            '#00FF00',
            '#0000FF',
            '#FFFF00',
            '#000000',
            '#FFFFFF',
        ]);
    });

    it('can get all palette IDs', function (): void {
        $ids = PaletteData::getAllIds();

        expect($ids)->toBeArray();
        expect($ids)->toContain('bw');
        expect($ids)->toContain('gray-4');
        expect($ids)->toContain('gray-16');
        expect($ids)->toContain('gray-256');
        expect($ids)->toContain('color-6a');
        expect($ids)->toContain('color-7a');
    });

    it('throws exception for invalid palette ID', function (): void {
        $palette = PaletteData::getById('invalid_palette');
        expect($palette)->toBeNull();
    });

    it('has correct properties for color-7a palette', function (): void {
        $palette = PaletteData::getById('color-7a');

        expect($palette->id)->toBe('color-7a');
        expect($palette->name)->toBe('Color (7 colors)');
        expect($palette->grays)->toBe(2);
        expect($palette->colors)->toBe([
            '#000000',
            '#FFFFFF',
            '#FF0000',
            '#00FF00',
            '#0000FF',
            '#FFFF00',
            '#FFA500',
        ]);
        expect($palette->frameworkClass)->toBe('');
    });
});
