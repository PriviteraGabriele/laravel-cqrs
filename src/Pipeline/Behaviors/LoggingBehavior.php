<?php

namespace TheCorps\LaravelCqrs\Pipeline\Behaviors;

use Closure;
use Illuminate\Support\Facades\Log;
use TheCorps\LaravelCqrs\Contracts\Interfaces\Pipeline\PipelineBehaviorInterface;

/**
 * Behavior that logs the dispatch and completion of every command or query.
 *
 * The log channel and level are read from the cqrs.logging config key.
 * Set cqrs.logging.channel to null to use the default Laravel log channel.
 */
class LoggingBehavior implements PipelineBehaviorInterface
{
    /**
     * Logs a message before and after executing the next behavior in the chain.
     *
     * @param object  $command The command or query passing through the pipeline.
     * @param Closure $next    The next behavior in the chain.
     *
     * @return mixed
     */
    public function handle(object $command, Closure $next): mixed
    {
        $name = class_basename($command);

        $this->log("[CQRS] {$name} dispatched");

        $result = $next($command);

        $this->log("[CQRS] {$name} completed");

        return $result;
    }

    /**
     * Writes a log message using the configured channel and level.
     *
     * @param string $message The message to log.
     *
     * @return void
     */
    private function log(string $message): void
    {
        $channel = config('cqrs.logging.channel');
        $level = config('cqrs.logging.level', 'debug');

        if ($channel !== null) {
            Log::channel($channel)->log($level, $message);
        } else {
            Log::log($level, $message);
        }
    }
}
