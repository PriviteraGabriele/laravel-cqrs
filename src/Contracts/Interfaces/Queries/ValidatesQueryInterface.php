<?php

namespace TheCorps\LaravelCqrs\Contracts\Interfaces\Queries;

/**
 * Interface for CQRS query validators.
 */
interface ValidatesQueryInterface
{
    /**
     * Validates the given query, throwing an exception on failure.
     *
     * @param object $query The query to validate.
     *
     * @return void
     */
    public function validate(object $query): void;
}
