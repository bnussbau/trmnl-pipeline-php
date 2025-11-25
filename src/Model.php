<?php

declare(strict_types=1);

namespace Bnussbau\TrmnlPipeline;

use Bnussbau\TrmnlPipeline\Data\ModelData;
use Bnussbau\TrmnlPipeline\Exceptions\ProcessingException;

enum Model: string
{
    case OG = 'og';
    case OG_PNG = 'og_png';
    case OG_BMP = 'og_bmp';
    case V2 = 'v2';
    case AMAZON_KINDLE_2024 = 'amazon_kindle_2024';
    case AMAZON_KINDLE_PAPERWHITE_6TH_GEN = 'amazon_kindle_paperwhite_6th_gen';
    case AMAZON_KINDLE_PAPERWHITE_7TH_GEN = 'amazon_kindle_paperwhite_7th_gen';
    case INKPLATE_10 = 'inkplate_10';
    case AMAZON_KINDLE_7 = 'amazon_kindle_7';
    case INKY_IMPRESSION_7_3 = 'inky_impression_7_3';
    case KOBO_LIBRA_2 = 'kobo_libra_2';
    case AMAZON_KINDLE_OASIS_2 = 'amazon_kindle_oasis_2';
    case OG_PLUS = 'og_plus';
    case KOBO_AURA_ONE = 'kobo_aura_one';
    case KOBO_AURA_HD = 'kobo_aura_hd';
    case INKY_IMPRESSION_13_3 = 'inky_impression_13_3';
    case M5_PAPER_S3 = 'm5_paper_s3';
    case AMAZON_KINDLE_SCRIBE = 'amazon_kindle_scribe';
    case SEEED_E1001 = 'seeed_e1001';
    case SEEED_E1002 = 'seeed_e1002';
    case WAVESHARE_4_26 = 'waveshare_4_26';
    case WAVESHARE_7_5_BW = 'waveshare_7_5_bw';

    /**
     * Get the model data from JSON
     *
     * @throws ProcessingException
     */
    public function getData(): ModelData
    {
        // OG case should resolve to og_plus data
        $modelName = $this === self::OG ? 'og_plus' : $this->value;

        return ModelData::getByName($modelName);
    }

    public function getLabel(): string
    {
        return $this->getData()->label;
    }

    public function getDescription(): string
    {
        return $this->getData()->description;
    }

    public function getWidth(): int
    {
        return $this->getData()->width;
    }

    public function getHeight(): int
    {
        return $this->getData()->height;
    }

    public function getColors(): int
    {
        return $this->getData()->colors;
    }

    public function getBitDepth(): int
    {
        return $this->getData()->bitDepth;
    }

    public function getScaleFactor(): float
    {
        return $this->getData()->scaleFactor;
    }

    public function getRotation(): int
    {
        return $this->getData()->rotation;
    }

    public function getMimeType(): string
    {
        return $this->getData()->mimeType;
    }

    public function getOffsetX(): int
    {
        return $this->getData()->offsetX;
    }

    public function getOffsetY(): int
    {
        return $this->getData()->offsetY;
    }

    public function getKind(): string
    {
        return $this->getData()->kind;
    }

    /**
     * Get palette IDs for this model
     *
     * @return array<string>
     *
     * @throws ProcessingException
     */
    public function getPaletteIds(): array
    {
        return $this->getData()->paletteIds;
    }

    /**
     * Get all models of a specific kind
     *
     * @param  string  $kind  The kind to filter by (trmnl, kindle, byod)
     * @return array<Model>
     *
     * @throws ProcessingException
     */
    public static function getByKind(string $kind): array
    {
        $modelData = ModelData::getByKind($kind);
        $modelNames = array_keys($modelData);

        return array_values(array_filter(
            self::cases(),
            fn (Model $model): bool => in_array($model->value, $modelNames, true)
        ));
    }

    /**
     * Get all TRMNL models
     *
     * @return array<Model>
     *
     * @throws ProcessingException
     */
    public static function getTrmnlModels(): array
    {
        return self::getByKind('trmnl');
    }

    /**
     * Get all Kindle models
     *
     * @return array<Model>
     *
     * @throws ProcessingException
     */
    public static function getKindleModels(): array
    {
        return self::getByKind('kindle');
    }

    /**
     * Get all BYOD (Bring Your Own Device) models
     *
     * @return array<Model>
     *
     * @throws ProcessingException
     */
    public static function getByodModels(): array
    {
        return self::getByKind('byod');
    }
}
