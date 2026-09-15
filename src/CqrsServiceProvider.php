<?php

namespace TheCorps\LaravelCqrs;

use TheCorps\LaravelCqrs\Bus\QueryBus;
use Illuminate\Support\ServiceProvider;
use TheCorps\LaravelCqrs\Bus\CommandBus;
use TheCorps\LaravelCqrs\Pipeline\Pipeline;
use TheCorps\LaravelCqrs\Pipeline\MetadataResolver;
use TheCorps\LaravelCqrs\Console\InstallAgentRulesCommand;
use TheCorps\LaravelCqrs\Pipeline\Behaviors\LoggingBehavior;
use TheCorps\LaravelCqrs\Pipeline\Behaviors\ValidationBehavior;
use TheCorps\LaravelCqrs\Pipeline\Behaviors\TransactionBehavior;
use TheCorps\LaravelCqrs\Pipeline\Behaviors\HandlerExecutionBehavior;
use TheCorps\LaravelCqrs\Contracts\Interfaces\Pipeline\PipelineBehaviorInterface;
use TheCorps\LaravelCqrs\Contracts\Attributes\Queries\QueryHandler as QueryHandlerAttr;
use TheCorps\LaravelCqrs\Contracts\Attributes\Queries\QueryValidator as QueryValidatorAttr;
use TheCorps\LaravelCqrs\Contracts\Attributes\Commands\CommandHandler as CommandHandlerAttr;
use TheCorps\LaravelCqrs\Contracts\Attributes\Commands\CommandValidator as CommandValidatorAttr;

/**
 * Service provider that registers the CommandBus and QueryBus with their
 * respective pipelines in the application container.
 */
class CqrsServiceProvider extends ServiceProvider
{
    /**
     * Registers the CQRS bindings in the application container.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cqrs.php', 'cqrs');

        $this->registerCommandBus();
        $this->registerQueryBus();
    }

    /**
     * Bootstraps package services and publishes the configuration file.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallAgentRulesCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/cqrs.php' => config_path('cqrs.php'),
            ], 'cqrs-config');
        }
    }

    /**
     * Configures and registers the CommandBus as a singleton in the container.
     *
     * @return void
     */
    private function registerCommandBus(): void
    {
        $resolver = new MetadataResolver(
            CommandHandlerAttr::class,
            CommandValidatorAttr::class
        );

        $behaviors = array_map(
            fn(string $class): PipelineBehaviorInterface => $this->makeBehavior($class, $resolver),
            config('cqrs.command_pipeline', [
                LoggingBehavior::class,
                ValidationBehavior::class,
                TransactionBehavior::class,
                HandlerExecutionBehavior::class,
            ])
        );

        $this->app->singleton(
            CommandBus::class,
            fn() => new CommandBus(new Pipeline($behaviors))
        );
    }

    /**
     * Configures and registers the QueryBus as a singleton in the container.
     *
     * @return void
     */
    private function registerQueryBus(): void
    {
        $resolver = new MetadataResolver(
            QueryHandlerAttr::class,
            QueryValidatorAttr::class
        );

        $behaviors = array_map(
            fn(string $class): PipelineBehaviorInterface => $this->makeBehavior($class, $resolver),
            config('cqrs.query_pipeline', [
                ValidationBehavior::class,
                HandlerExecutionBehavior::class,
            ])
        );

        $this->app->singleton(
            QueryBus::class,
            fn() => new QueryBus(new Pipeline($behaviors))
        );
    }

    /**
     * Instantiates a pipeline behavior, injecting the MetadataResolver for
     * built-in behaviors that require it, or resolving custom ones from the container.
     *
     * @param string           $class    Fully qualified behavior class name.
     * @param MetadataResolver $resolver The resolver to inject into built-in behaviors.
     *
     * @return PipelineBehaviorInterface
     */
    private function makeBehavior(string $class, MetadataResolver $resolver): PipelineBehaviorInterface
    {
        return match ($class) {
            ValidationBehavior::class => new ValidationBehavior($resolver),
            HandlerExecutionBehavior::class => new HandlerExecutionBehavior($resolver),
            default => app($class),
        };
    }
}
