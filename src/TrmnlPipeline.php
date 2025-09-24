<?php

declare(strict_types=1);

namespace Bnussbau\TrmnlPipeline;

use Bnussbau\TrmnlPipeline\Exceptions\ProcessingException;
use League\Pipeline\Pipeline;

/**
 * Main pipeline class for e-ink image processing
 */
class TrmnlPipeline
{
    private ?Model $model = null;

    private Pipeline $pipeline;

    private static bool $isFake = false;

    public function __construct()
    {
        $this->pipeline = new Pipeline;
    }

    /**
     * Set the model for automatic configuration
     */
    public function model(Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the current model
     */
    public function getModel(): ?Model
    {
        return $this->model;
    }

    /**
     * Add a stage to the pipeline
     */
    public function pipe(StageInterface $stage): self
    {
        // Automatically configure the stage with the model if it's set
        if ($this->model instanceof Model && method_exists($stage, 'configureFromModel')) {
            $stage->configureFromModel($this->model);
        }

        $this->pipeline = $this->pipeline->pipe(fn ($payload): mixed => $stage($payload));

        return $this;
    }

    /**
     * Process the pipeline
     *
     * @return mixed The processed result
     *
     * @throws ProcessingException
     */
    public function process(): mixed
    {
        try {
            return $this->pipeline->process(null);
        } catch (\Exception $e) {
            throw new ProcessingException(
                'Pipeline processing failed: '.$e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Enable fake mode for testing - prevents actual Browsershot and Imagick operations
     */
    public static function fake(): void
    {
        self::$isFake = true;
    }

    /**
     * Disable fake mode
     */
    public static function restore(): void
    {
        self::$isFake = false;
    }

    /**
     * Check if fake mode is enabled
     */
    public static function isFake(): bool
    {
        return self::$isFake;
    }
}
