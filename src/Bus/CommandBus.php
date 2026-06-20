<?php

namespace TheCorps\LaravelCqrs\Bus;

use TheCorps\LaravelCqrs\Pipeline\Pipeline;
use TheCorps\LaravelCqrs\Contracts\Interfaces\Commands\CommandInterface;

/**
 * CQRS command bus: routes commands through the configured pipeline.
 */
class CommandBus
{
    /**
     * Creates a new command bus instance.
     *
     * @param Pipeline $pipeline The behavior pipeline to pass commands through.
     */
    public function __construct(
        private readonly Pipeline $pipeline,
    ) {}

    /**
     * Dispatches a command through the pipeline and returns the result.
     *
     * @param CommandInterface $command The command to dispatch.
     *
     * @return mixed
     */
    public function dispatch(CommandInterface $command): mixed
    {
        return $this->pipeline->handle($command);
    }
}
