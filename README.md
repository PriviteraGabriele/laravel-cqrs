# thecorps/laravel-cqrs

> [🇮🇹 Leggi in italiano](README.it.md)

Lightweight CQRS pipeline infrastructure for Laravel applications, built on PHP 8 attributes.

Provides a `CommandBus` and a `QueryBus` that route your commands and queries through a fully configurable pipeline of behaviors — logging, validation, database transactions, and handler execution — with zero boilerplate in the consuming code.

---

## Requirements

| Dependency | Version            |
| ---------- | ------------------ |
| PHP        | `^8.2`             |
| Laravel    | `^11.0` or `^12.0` |

---

## Installation

```bash
composer require thecorps/laravel-cqrs
```

Laravel's auto-discovery registers the `CqrsServiceProvider` and the `CommandBus` / `QueryBus` facades automatically. No manual registration needed.

### Publishing the configuration file

```bash
php artisan vendor:publish --tag=cqrs-config
```

This creates `config/cqrs.php` in your application where you can customize the pipeline and logging settings.

---

## How it works

```
CommandBus::dispatch(new CreateUserCommand(...))
    │
    ▼
LoggingBehavior           → logs "[CQRS] CreateUserCommand dispatched"
    │
    ▼
ValidationBehavior        → resolves #[CommandValidator] and calls validate()
    │
    ▼
TransactionBehavior       → wraps everything below in DB::transaction()
    │
    ▼
HandlerExecutionBehavior  → resolves #[CommandHandler] and calls handle()
    │
    ▼
CreateUserCommandHandler::handle(CreateUserCommand $command)
```

Queries follow the same pattern but skip `LoggingBehavior` and `TransactionBehavior` by default.

---

## Usage

### 1 — Command

A command is an immutable DTO that implements `CommandInterface` and declares its handler via the `#[CommandHandler]` attribute.

```php
use TheCorps\LaravelCqrs\Contracts\Interfaces\Commands\CommandInterface;
use TheCorps\LaravelCqrs\Contracts\Attributes\Commands\CommandHandler;

#[CommandHandler(CreateUserCommandHandler::class)]
final class CreateUserCommand implements CommandInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
```

### 2 — Command Handler

```php
use TheCorps\LaravelCqrs\Contracts\Interfaces\Commands\CommandHandlerInterface;

class CreateUserCommandHandler implements CommandHandlerInterface
{
    public function handle(CreateUserCommand $command): User
    {
        return User::create([
            'name'  => $command->name,
            'email' => $command->email,
        ]);
    }
}
```

### 3 — Dispatch

```php
use TheCorps\LaravelCqrs\Facades\CommandBus;

$user = CommandBus::dispatch(new CreateUserCommand(
    name:  $request->validated('name'),
    email: $request->validated('email'),
));
```

---

### 4 — Query

```php
use TheCorps\LaravelCqrs\Contracts\Interfaces\Queries\QueryInterface;
use TheCorps\LaravelCqrs\Contracts\Attributes\Queries\QueryHandler;

#[QueryHandler(GetUserQueryHandler::class)]
final class GetUserQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId,
    ) {}
}
```

```php
use TheCorps\LaravelCqrs\Contracts\Interfaces\Queries\QueryHandlerInterface;

class GetUserQueryHandler implements QueryHandlerInterface
{
    public function handle(GetUserQuery $query): ?User
    {
        return User::find($query->userId);
    }
}
```

```php
use TheCorps\LaravelCqrs\Facades\QueryBus;

$user = QueryBus::dispatch(new GetUserQuery(userId: $id));
```

---

### 5 — Validator (optional)

Validators contain domain rules that go beyond HTTP input validation. They are opt-in: without `#[CommandValidator]`, the `ValidationBehavior` is a no-op.

```php
#[CommandHandler(CreateUserCommandHandler::class)]
#[CommandValidator(CreateUserCommandValidator::class)]
final class CreateUserCommand implements CommandInterface { ... }
```

```php
use TheCorps\LaravelCqrs\Contracts\Interfaces\Commands\ValidatesCommandInterface;

class CreateUserCommandValidator implements ValidatesCommandInterface
{
    public function validate(object $command): void
    {
        if (User::where('email', $command->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email is already registered.',
            ]);
        }
    }
}
```

---

## Configuration

After publishing, `config/cqrs.php` exposes three configuration areas.

### Pipeline composition

```php
'command_pipeline' => [
    LoggingBehavior::class,
    ValidationBehavior::class,
    TransactionBehavior::class,
    HandlerExecutionBehavior::class,  // must always be last
],

'query_pipeline' => [
    ValidationBehavior::class,
    HandlerExecutionBehavior::class,  // must always be last
],
```

You can add, remove, or reorder behaviors freely. The order in the array is the execution order.

### Custom behaviors

Any class implementing `PipelineBehaviorInterface` can be inserted into the pipeline. Custom behaviors are resolved from the Laravel container, so they support constructor injection.

```php
use Closure;
use TheCorps\LaravelCqrs\Contracts\Interfaces\Pipeline\PipelineBehaviorInterface;

class RateLimitingBehavior implements PipelineBehaviorInterface
{
    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(object $command, Closure $next): mixed
    {
        // your cross-cutting logic here
        return $next($command);
    }
}
```

```php
// config/cqrs.php
'command_pipeline' => [
    LoggingBehavior::class,
    RateLimitingBehavior::class,   // ← inserted
    ValidationBehavior::class,
    TransactionBehavior::class,
    HandlerExecutionBehavior::class,
],
```

### Logging

```php
'logging' => [
    'channel' => null,     // null = default Laravel log channel; or 'daily', 'slack', etc.
    'level'   => 'debug',  // any PSR-3 level: debug, info, notice, warning, error
],
```

---

## Package structure

```
src/
├── Contracts/
│   ├── Attributes/
│   │   ├── Commands/
│   │   │   ├── CommandHandler.php     ← #[CommandHandler(HandlerClass::class)]
│   │   │   └── CommandValidator.php   ← #[CommandValidator(ValidatorClass::class)]
│   │   └── Queries/
│   │       ├── QueryHandler.php       ← #[QueryHandler(HandlerClass::class)]
│   │       └── QueryValidator.php     ← #[QueryValidator(ValidatorClass::class)]
│   └── Interfaces/
│       ├── Commands/
│       │   ├── CommandInterface.php           ← implement on every Command DTO
│       │   ├── CommandHandlerInterface.php    ← implement on every CommandHandler
│       │   └── ValidatesCommandInterface.php  ← implement on every CommandValidator
│       ├── Queries/
│       │   ├── QueryInterface.php             ← implement on every Query DTO
│       │   ├── QueryHandlerInterface.php      ← implement on every QueryHandler
│       │   └── ValidatesQueryInterface.php    ← implement on every QueryValidator
│       └── Pipeline/
│           └── PipelineBehaviorInterface.php  ← implement for custom behaviors
├── Pipeline/
│   ├── Behaviors/
│   │   ├── LoggingBehavior.php           ← logs dispatch and completion
│   │   ├── ValidationBehavior.php        ← runs the validator if declared
│   │   ├── TransactionBehavior.php       ← wraps in DB::transaction()
│   │   └── HandlerExecutionBehavior.php  ← resolves and calls the handler
│   ├── MetadataResolver.php  ← reads PHP attributes via reflection (statically cached)
│   └── Pipeline.php          ← composes and executes the behavior chain
├── Bus/
│   ├── CommandBus.php  ← concrete bus registered in the container
│   └── QueryBus.php
├── Facades/
│   ├── CommandBus.php  ← Laravel facade
│   └── QueryBus.php
└── CqrsServiceProvider.php  ← registers buses, merges config, publishes files
```

---

## Best practices

**Keep Commands and Queries as immutable DTOs.**
Use `readonly` constructor properties. A command must carry exactly what it needs — no optional nullable fields that mask missing data.

**Separate HTTP validation from domain validation.**
Use Laravel `FormRequest` to check input format, presence, and types. Use `CommandValidator` for domain rules: uniqueness, state machine transitions, cross-aggregate constraints.

```
FormRequest       → is the email a valid format? is the field present?
CommandValidator  → is this email already taken? can this subscription be paused?
```

**Handlers must be thin orchestrators.**
Keep business logic in models, domain services, or value objects. The handler's only job is to coordinate them.

**Queries must never write.**
If you find yourself calling `save()` inside a query handler, move that logic to a command.

**`HandlerExecutionBehavior` must always be last.**
Any behavior placed after it will never execute.

**Do not add `TransactionBehavior` to the query pipeline.**
Wrapping read-only queries in transactions is unnecessary overhead. The default configuration already reflects this.
