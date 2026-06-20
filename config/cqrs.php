<?php

use TheCorps\LaravelCqrs\Pipeline\Behaviors\LoggingBehavior;
use TheCorps\LaravelCqrs\Pipeline\Behaviors\ValidationBehavior;
use TheCorps\LaravelCqrs\Pipeline\Behaviors\TransactionBehavior;
use TheCorps\LaravelCqrs\Pipeline\Behaviors\HandlerExecutionBehavior;

return [

    /*
    |--------------------------------------------------------------------------
    | Command Pipeline
    |--------------------------------------------------------------------------
    |
    | The ordered list of behaviors that every command passes through before
    | reaching its handler. You can add, remove, or reorder behaviors here,
    | including your own custom classes that implement PipelineBehaviorInterface.
    |
    | Built-in behaviors:
    |   - LoggingBehavior — logs dispatch and completion (debug)
    |   - ValidationBehavior — runs the CommandValidator if present
    |   - TransactionBehavior — wraps execution in a DB transaction
    |   - HandlerExecutionBehavior — resolves and calls the CommandHandler
    |
    */

    'command_pipeline' => [
        LoggingBehavior::class,
        ValidationBehavior::class,
        TransactionBehavior::class,
        HandlerExecutionBehavior::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Pipeline
    |--------------------------------------------------------------------------
    |
    | The ordered list of behaviors that every query passes through before
    | reaching its handler. Queries intentionally skip logging and transactions
    | by default, but you can add them back or insert custom behaviors.
    |
    */

    'query_pipeline' => [
        ValidationBehavior::class,
        HandlerExecutionBehavior::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Controls how the LoggingBehavior writes messages.
    |
    |   channel — the Laravel log channel to use (null = default channel)
    |   level — PSR-3 log level: debug, info, notice, warning, error
    |
    */

    'logging' => [
        'channel' => null,
        'level' => 'debug',
    ],

];
