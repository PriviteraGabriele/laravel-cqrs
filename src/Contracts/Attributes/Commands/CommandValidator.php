<?php

namespace TheCorps\LaravelCqrs\Contracts\Attributes\Commands;

use Attribute;

/**
 * Attribute that binds a command class to its validator class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class CommandValidator
{
    /**
     * Creates a new attribute instance.
     *
     * @param string $validatorClass Fully qualified class name of the command validator.
     */
    public function __construct(
        public readonly string $validatorClass,
    ) {}
}
