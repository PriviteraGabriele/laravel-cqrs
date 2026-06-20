<?php

namespace TheCorps\LaravelCqrs\Facades;

use Illuminate\Support\Facades\Facade;
use TheCorps\LaravelCqrs\Contracts\Interfaces\Queries\QueryInterface;

/**
 * Facade for the CQRS query bus.
 *
 * @method static mixed dispatch(QueryInterface $query)
 */
class QueryBus extends Facade
{
    /**
     * Returns the binding key for the underlying service in the container.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \TheCorps\LaravelCqrs\Bus\QueryBus::class;
    }
}
