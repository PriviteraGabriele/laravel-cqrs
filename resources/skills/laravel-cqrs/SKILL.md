---
name: laravel-cqrs
description: Apply TheCorps Laravel CQRS conventions when creating or changing commands, queries, handlers, validators, dispatching, or CQRS pipeline configuration.
---

<!-- laravel-cqrs-managed-skill -->

# Laravel CQRS conventions

Apply these rules whenever this project uses `thecorps/laravel-cqrs` and the task affects CQRS code.

## Classify the use case first

- Use a command for every state change. A query handler must never create, update, delete, save, dispatch a write, or cause another side effect.
- If a requested query needs a write, design a command instead and dispatch it through `CommandBus`.
- Keep commands and queries focused on one use case. Do not add nullable catch-all fields that hide missing input.

## Required application structure

Place each use case under its feature:

```text
app/Domain/{Feature}/
  Commands/{UseCase}/
    {UseCase}Command.php
    {UseCase}Handler.php
    {UseCase}Validator.php       # only when domain validation is needed
  Queries/{UseCase}/
    {UseCase}Query.php
    {UseCase}Handler.php
    {UseCase}Validator.php       # only when domain validation is needed
```

## Implement the library contracts

- A command is a `final` immutable DTO implementing `CommandInterface`, with `#[CommandHandler(...)]` and, when needed, `#[CommandValidator(...)]`.
- A query is a `final` immutable DTO implementing `QueryInterface`, with `#[QueryHandler(...)]` and, when needed, `#[QueryValidator(...)]`.
- Command and query handlers implement their corresponding handler interface and expose `handle()` for the specific DTO.
- Validators implement `ValidatesCommandInterface` or `ValidatesQueryInterface` and keep the required `validate(object $command): void` signature.
- Dispatch commands only through `CommandBus::dispatch()` and queries only through `QueryBus::dispatch()`. Do not call a handler directly.

## Keep responsibilities separate

- Use Laravel `FormRequest` for transport-level presence, type, and format checks. Use CQRS validators for domain rules such as uniqueness, allowed state transitions, and cross-aggregate constraints.
- Handlers coordinate models, domain services, and value objects; do not move business rules into the handler merely to make a use case work.
- Do not alter the configured pipeline without an explicit task requirement. `HandlerExecutionBehavior` must be last, and `TransactionBehavior` belongs only to the command pipeline.

## Completion check

Before finishing, verify that the use case is in the correct feature directory, uses the correct DTO/attribute/interface/bus, has no query-side writes, keeps domain validation separate from HTTP validation, and preserves the pipeline invariants.
