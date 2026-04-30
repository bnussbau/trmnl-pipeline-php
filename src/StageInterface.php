<?php

declare(strict_types=1);

namespace Bnussbau\EpaperPipeline;

/**
 * Interface for pipeline stages
 */
interface StageInterface
{
    /**
     * Process the payload through this stage
     *
     * @param  mixed  $payload  The payload to process
     * @return mixed The processed payload
     */
    public function __invoke(mixed $payload): mixed;
}
