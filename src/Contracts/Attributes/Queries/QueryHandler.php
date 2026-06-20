<?php

namespace TheCorps\LaravelCqrs\Contracts\Attributes\Queries;

use Attribute;

/**
 * Attribute that binds a query class to its handler class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class QueryHandler
{
    /**
     * Creates a new attribute instance.
     *
     * @param string $handlerClass Fully qualified class name of the query handler.
     */
    public function __construct(
        public readonly string $handlerClass,
    ) {}
}
