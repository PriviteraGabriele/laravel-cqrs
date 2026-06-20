<?php

namespace TheCorps\LaravelCqrs\Contracts\Interfaces\Pipeline;

use Closure;

/**
 * Interface for CQRS pipeline behaviors.
 *
 * Each behavior wraps the next step in the chain, similar to middleware.
 * Behaviors are composed in the order defined in the cqrs.php config file.
 */
interface PipelineBehaviorInterface
{
    /**
     * Handles the command or query and delegates to the next behavior in the chain.
     *
     * @param object  $command The command or query passing through the pipeline.
     * @param Closure $next    The next behavior to invoke.
     *
     * @return mixed
     */
    public function handle(object $command, Closure $next): mixed;
}
