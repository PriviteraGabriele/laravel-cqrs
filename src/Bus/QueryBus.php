<?php

namespace TheCorps\LaravelCqrs\Bus;

use TheCorps\LaravelCqrs\Pipeline\Pipeline;
use TheCorps\LaravelCqrs\Contracts\Interfaces\Queries\QueryInterface;

/**
 * CQRS query bus: routes queries through the configured pipeline.
 */
class QueryBus
{
    /**
     * Creates a new query bus instance.
     *
     * @param Pipeline $pipeline The behavior pipeline to pass queries through.
     */
    public function __construct(
        private readonly Pipeline $pipeline,
    ) {}

    /**
     * Dispatches a query through the pipeline and returns the result.
     *
     * @param QueryInterface $query The query to dispatch.
     *
     * @return mixed
     */
    public function dispatch(QueryInterface $query): mixed
    {
        return $this->pipeline->handle($query);
    }
}
