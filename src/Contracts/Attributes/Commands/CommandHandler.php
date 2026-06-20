<?php

namespace TheCorps\LaravelCqrs\Contracts\Attributes\Commands;

use Attribute;

/**
 * Attribute that binds a command class to its handler class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class CommandHandler
{
    /**
     * Creates a new attribute instance.
     *
     * @param string $handlerClass Fully qualified class name of the command handler.
     */
    public function __construct(
        public readonly string $handlerClass,
    ) {}
}
