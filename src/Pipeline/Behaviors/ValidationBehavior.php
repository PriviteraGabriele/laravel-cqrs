<?php

namespace TheCorps\LaravelCqrs\Pipeline\Behaviors;

use Closure;
use TheCorps\LaravelCqrs\Pipeline\MetadataResolver;
use TheCorps\LaravelCqrs\Contracts\Interfaces\Pipeline\PipelineBehaviorInterface;

/**
 * Behavior that validates the command or query before continuing through the pipeline.
 *
 * If no validator is bound to the command/query (i.e. no #[CommandValidator] attribute),
 * this behavior is a no-op and passes control to the next step immediately.
 */
class ValidationBehavior implements PipelineBehaviorInterface
{
    /**
     * Creates a new behavior instance.
     *
     * @param MetadataResolver $resolver The resolver used to retrieve the validator class.
     */
    public function __construct(
        private readonly MetadataResolver $resolver,
    ) {}

    /**
     * Resolves the validator via the resolver, executes it if present, then delegates to the next behavior.
     *
     * @param object  $command The command or query to validate.
     * @param Closure $next    The next behavior in the chain.
     *
     * @return mixed
     */
    public function handle(object $command, Closure $next): mixed
    {
        $validatorClass = $this->resolver->getValidatorClass($command);

        if ($validatorClass !== null) {
            app($validatorClass)->validate($command);
        }

        return $next($command);
    }
}
