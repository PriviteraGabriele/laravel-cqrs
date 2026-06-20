<?php

namespace TheCorps\LaravelCqrs\Facades;

use Illuminate\Support\Facades\Facade;
use TheCorps\LaravelCqrs\Contracts\Interfaces\Commands\CommandInterface;

/**
 * Facade for the CQRS command bus.
 *
 * @method static mixed dispatch(CommandInterface $command)
 */
class CommandBus extends Facade
{
    /**
     * Returns the binding key for the underlying service in the container.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \TheCorps\LaravelCqrs\Bus\CommandBus::class;
    }
}
