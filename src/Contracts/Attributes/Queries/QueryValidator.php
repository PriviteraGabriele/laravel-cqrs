<?php

namespace TheCorps\LaravelCqrs\Contracts\Attributes\Queries;

use Attribute;

/**
 * Attribute that binds a query class to its validator class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class QueryValidator
{
    /**
     * Creates a new attribute instance.
     *
     * @param string $validatorClass Fully qualified class name of the query validator.
     */
    public function __construct(
        public readonly string $validatorClass,
    ) {}
}
