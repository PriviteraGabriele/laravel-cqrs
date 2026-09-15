# Coding-agent integration

`thecorps/laravel-cqrs` can install shared CQRS guidance for Codex and Claude Code in a consuming Laravel application.

## Installation

After installing the package, run this command from the application root:

```bash
php artisan cqrs:install-agent-rules
```

It is opt-in. Installing the Composer package never edits application instruction files automatically.

## Generated files

The installer writes the following files relative to Laravel's base path:

```text
AGENTS.md
CLAUDE.md
.agents/skills/laravel-cqrs/SKILL.md
.claude/skills/laravel-cqrs/SKILL.md
```

The root instruction files contain a managed section delimited by these markers:

```text
<!-- laravel-cqrs-agent-rules:start -->
<!-- laravel-cqrs-agent-rules:end -->
```

Existing content outside those markers is left unchanged. Re-running the command replaces only that section and skills containing the package's managed-skill marker. If either skill path already contains an unmanaged `SKILL.md`, the command stops before writing any root files.

## Required CQRS rules

The generated skill applies these conventions whenever an agent creates or changes CQRS code:

- Classify each use case first. Commands perform every state change; queries are read-only and cannot create, update, delete, save, or cause another side effect.
- Put each use case in its feature directory:

  ```text
  app/Domain/{Feature}/
    Commands/{UseCase}/
      {UseCase}Command.php
      {UseCase}Handler.php
      {UseCase}Validator.php
    Queries/{UseCase}/
      {UseCase}Query.php
      {UseCase}Handler.php
      {UseCase}Validator.php
  ```

  Validators are present only when domain validation is needed.

- Commands and queries are `final`, immutable DTOs and implement their matching library interface. They declare their handler with `#[CommandHandler]` or `#[QueryHandler]`, and declare a validator attribute only when needed.
- Handlers implement the relevant handler interface. Commands are dispatched with `CommandBus::dispatch()` and queries with `QueryBus::dispatch()`; handlers are never invoked directly.
- Use `FormRequest` for HTTP input validation and CQRS validators for domain invariants such as uniqueness and state transitions. Keep handlers as thin orchestrators around models, domain services, and value objects.
- Preserve pipeline invariants: do not change the pipeline unless the task requires it, keep `HandlerExecutionBehavior` last, and use `TransactionBehavior` only in the command pipeline.

Before completing a task, the agent verifies the classification, feature layout, contracts, bus dispatch, validation boundary, absence of query writes, and pipeline order.

## Enforcement boundary

These files make the conventions available automatically to supported coding agents and direct them to treat the rules as mandatory. They are instructions, not a static analyzer or runtime protection. Retain normal code review and add architecture checks separately if your project needs technical enforcement.
