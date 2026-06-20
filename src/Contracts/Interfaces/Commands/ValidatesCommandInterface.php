<?php

namespace TheCorps\LaravelCqrs\Contracts\Interfaces\Commands;

/**
 * Interface for CQRS command validators.
 */
interface ValidatesCommandInterface
{
    /**
     * Validates the given command, throwing an exception on failure.
     *
     * @param object $command The command to validate.
     *
     * @return void
     */
    public function validate(object $command): void;
}
