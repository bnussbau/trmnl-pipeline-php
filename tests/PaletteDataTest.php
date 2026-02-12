<?php

declare(strict_types=1);

use Bnussbau\TrmnlPipeline\Data\PaletteData;
use Bnussbau\TrmnlPipeline\Exceptions\ProcessingException;

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

    it('throws when palettes JSON file does not exist', function (): void {
        $missingPath = sys_get_temp_dir().'/palettes-nonexistent-'.uniqid().'.json';

        expect(fn () => PaletteData::loadFromJson($missingPath))
            ->toThrow(ProcessingException::class, 'Palettes JSON file not found at: '.$missingPath);
    });

    it('throws when palettes JSON file cannot be read', function (): void {
        $tmpFile = tempnam(sys_get_temp_dir(), 'palettes');
        if ($tmpFile === false) {
            throw new \RuntimeException('Failed to create temp file');
        }
        chmod($tmpFile, 0000);
        try {
            expect(fn () => PaletteData::loadFromJson($tmpFile))
                ->toThrow(ProcessingException::class, 'Failed to read palettes JSON file');
        } finally {
            chmod($tmpFile, 0600);
            unlink($tmpFile);
        }
    });

    it('throws when palettes JSON is invalid', function (): void {
        $tmpFile = tempnam(sys_get_temp_dir(), 'palettes');
        if ($tmpFile === false) {
            throw new \RuntimeException('Failed to create temp file');
        }
        try {
            file_put_contents($tmpFile, '{ invalid json }');
            expect(fn () => PaletteData::loadFromJson($tmpFile))
                ->toThrow(ProcessingException::class, 'Invalid JSON in palettes file:');
        } finally {
            unlink($tmpFile);
        }
    });

    it('throws when palettes JSON does not decode to array', function (): void {
        $tmpFile = tempnam(sys_get_temp_dir(), 'palettes');
        if ($tmpFile === false) {
            throw new \RuntimeException('Failed to create temp file');
        }
        try {
            file_put_contents($tmpFile, '123');
            expect(fn () => PaletteData::loadFromJson($tmpFile))
                ->toThrow(ProcessingException::class, 'Invalid JSON structure: expected array');
        } finally {
            unlink($tmpFile);
        }
    });

    it('throws when palettes JSON is missing data array', function (): void {
        $tmpFile = tempnam(sys_get_temp_dir(), 'palettes');
        if ($tmpFile === false) {
            throw new \RuntimeException('Failed to create temp file');
        }
        try {
            file_put_contents($tmpFile, '{}');
            expect(fn () => PaletteData::loadFromJson($tmpFile))
                ->toThrow(ProcessingException::class, "Invalid palettes JSON structure: missing 'data' array");
        } finally {
            unlink($tmpFile);
        }
    });

    it('throws when palette entry is missing id field', function (): void {
        $tmpFile = tempnam(sys_get_temp_dir(), 'palettes');
        if ($tmpFile === false) {
            throw new \RuntimeException('Failed to create temp file');
        }
        try {
            file_put_contents($tmpFile, '{"data":[{"name":"no-id"}]}');
            expect(fn () => PaletteData::loadFromJson($tmpFile))
                ->toThrow(ProcessingException::class, "Palette data missing required 'id' field");
        } finally {
            unlink($tmpFile);
        }
    });
});
