<?php

namespace TheCorps\LaravelCqrs\Pipeline;

use Closure;
use TheCorps\LaravelCqrs\Contracts\Interfaces\Pipeline\PipelineBehaviorInterface;

/**
 * CQRS pipeline: composes behaviors into a chain and executes them in sequence.
 */
class Pipeline
{
    /** @param PipelineBehaviorInterface[] $behaviors */
    public function __construct(
        private readonly array $behaviors,
    ) {}

    /**
     * Executes the command or query through the entire behavior chain.
     *
     * @param object $command The command or query to process.
     *
     * @return mixed
     */
    public function handle(object $command): mixed
    {
        $chain = array_reduce(
            array_reverse($this->behaviors),
            fn(Closure $carry, PipelineBehaviorInterface $behavior): Closure =>
            fn(object $command): mixed => $behavior->handle($command, $carry),
            fn(object $command): mixed => null,
        );

        return $chain($command);
    }
}
