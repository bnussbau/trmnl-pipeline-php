<?php

declare(strict_types=1);

namespace Bnussbau\TrmnlPipeline\Data;

use Bnussbau\TrmnlPipeline\Exceptions\ProcessingException;

/**
 * Palette data structure loaded from palettes.json
 */
readonly class PaletteData
{
    public function __construct(
        public string $id,
        public string $name,
        public int $grays,
        /** @var array<string>|null */
        public ?array $colors,
        public string $frameworkClass,
    ) {}

    /**
     * Load palette data from JSON file
     *
     * @param  string|null  $jsonPath  Optional path to JSON file (defaults to palettes.json in package)
     * @return array<string, PaletteData>
     *
     * @throws ProcessingException
     */
    public static function loadFromJson(?string $jsonPath = null): array
    {
        $jsonPath = $jsonPath ?? __DIR__.'/palettes.json';

        if (! file_exists($jsonPath)) {
            throw new ProcessingException("Palettes JSON file not found at: {$jsonPath}");
        }

        $jsonContent = file_get_contents($jsonPath);
        if ($jsonContent === false) {
            throw new ProcessingException('Failed to read palettes JSON file');
        }

        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ProcessingException('Invalid JSON in palettes file: '.json_last_error_msg());
        }

        if (! is_array($data)) {
            throw new ProcessingException('Invalid JSON structure: expected array');
        }

        if (! isset($data['data']) || ! is_array($data['data'])) {
            throw new ProcessingException("Invalid palettes JSON structure: missing 'data' array");
        }

        $palettes = [];
        foreach ($data['data'] as $paletteData) {
            if (! isset($paletteData['id'])) {
                throw new ProcessingException("Palette data missing required 'id' field");
            }

            $palettes[$paletteData['id']] = new self(
                id: $paletteData['id'],
                name: $paletteData['name'] ?? '',
                grays: (int) ($paletteData['grays'] ?? 0),
                colors: $paletteData['colors'] ?? null,
                frameworkClass: $paletteData['framework_class'] ?? '',
            );
        }

        return $palettes;
    }

    public static function getById(string $id): ?self
    {
        $palettes = self::loadFromJson();

        if (! isset($palettes[$id])) {
            return null;
        }

        return $palettes[$id];
    }

    /**
     * Get all available palette IDs
     *
     * @return array<string>
     *
     * @throws ProcessingException
     */
    public static function getAllIds(): array
    {
        $palettes = self::loadFromJson();

        return array_keys($palettes);
    }
}
