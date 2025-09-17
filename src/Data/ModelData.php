<?php

declare(strict_types=1);

namespace Bnussbau\TrmnlPipeline\Data;

use Bnussbau\TrmnlPipeline\Exceptions\ProcessingException;

/**
 * Model data structure loaded from models.json
 */
readonly class ModelData
{
    public function __construct(
        public string $name,
        public string $label,
        public string $description,
        public int $width,
        public int $height,
        public int $colors,
        public int $bitDepth,
        public float $scaleFactor,
        public int $rotation,
        public string $mimeType,
        public int $offsetX,
        public int $offsetY,
        public string $publishedAt,
        public string $kind,
    ) {}

    /**
     * Load model data from JSON file
     *
     * @return array<string, ModelData>
     *
     * @throws ProcessingException
     */
    public static function loadFromJson(): array
    {
        $jsonPath = __DIR__.'/models.json';

        if (! file_exists($jsonPath)) {
            throw new ProcessingException("Models JSON file not found at: {$jsonPath}");
        }

        $jsonContent = file_get_contents($jsonPath);
        if ($jsonContent === false) {
            throw new ProcessingException('Failed to read models JSON file');
        }

        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ProcessingException('Invalid JSON in models file: '.json_last_error_msg());
        }

        if (! is_array($data)) {
            throw new ProcessingException('Invalid JSON structure: expected array');
        }

        if (! isset($data['data']) || ! is_array($data['data'])) {
            throw new ProcessingException("Invalid models JSON structure: missing 'data' array");
        }

        $models = [];
        foreach ($data['data'] as $modelData) {
            if (! isset($modelData['name'])) {
                throw new ProcessingException("Model data missing required 'name' field");
            }

            $models[$modelData['name']] = new self(
                name: $modelData['name'],
                label: $modelData['label'] ?? '',
                description: $modelData['description'] ?? '',
                width: (int) ($modelData['width'] ?? 0),
                height: (int) ($modelData['height'] ?? 0),
                colors: (int) ($modelData['colors'] ?? 0),
                bitDepth: (int) ($modelData['bit_depth'] ?? 0),
                scaleFactor: (float) ($modelData['scale_factor'] ?? 1.0),
                rotation: (int) ($modelData['rotation'] ?? 0),
                mimeType: $modelData['mime_type'] ?? 'image/png',
                offsetX: (int) ($modelData['offset_x'] ?? 0),
                offsetY: (int) ($modelData['offset_y'] ?? 0),
                publishedAt: $modelData['published_at'] ?? '',
                kind: $modelData['kind'] ?? '',
            );
        }

        return $models;
    }

    /**
     * Get model data by name
     *
     * @throws ProcessingException
     */
    public static function getByName(string $name): self
    {
        $models = self::loadFromJson();

        if (! isset($models[$name])) {
            throw new ProcessingException("Model '{$name}' not found in models data");
        }

        return $models[$name];
    }

    /**
     * Get all models of a specific kind
     *
     * @return array<ModelData>
     *
     * @throws ProcessingException
     */
    public static function getByKind(string $kind): array
    {
        $models = self::loadFromJson();

        return array_filter(
            $models,
            fn (ModelData $model): bool => $model->kind === $kind
        );
    }

    /**
     * Get all available model names
     *
     * @return array<string>
     *
     * @throws ProcessingException
     */
    public static function getAllNames(): array
    {
        $models = self::loadFromJson();

        return array_keys($models);
    }
}
