<?php

namespace TheCorps\LaravelCqrs\Pipeline\Behaviors;

use Closure;
use Illuminate\Support\Facades\DB;
use TheCorps\LaravelCqrs\Contracts\Interfaces\Pipeline\PipelineBehaviorInterface;

/**
 * Behavior that wraps the next behavior in the chain inside a database transaction.
 *
 * Any exception thrown downstream will automatically roll back the transaction.
 * This behavior should only be included in the command pipeline, not the query pipeline.
 */
class TransactionBehavior implements PipelineBehaviorInterface
{
    /**
     * Wraps the execution of the next behavior in a database transaction.
     *
     * @param object  $command The command or query passing through the pipeline.
     * @param Closure $next    The next behavior in the chain.
     *
     * @return mixed
     */
    public function handle(object $command, Closure $next): mixed
    {
        return DB::transaction(
            fn(): mixed => $next($command)
        );
    }
}
