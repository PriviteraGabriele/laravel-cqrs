# thecorps/laravel-cqrs

> [🇬🇧 Read in English](README.md)

Infrastruttura CQRS leggera per applicazioni Laravel, costruita su attributi PHP 8.

Fornisce un `CommandBus` e un `QueryBus` che instradano comandi e query attraverso una pipeline di behavior completamente configurabile — logging, validazione, transazioni database ed esecuzione dell'handler — senza boilerplate nel codice consumer.

---

## Requisiti

| Dipendenza | Versione          |
| ---------- | ----------------- |
| PHP        | `^8.2`            |
| Laravel    | `^11.0` o `^12.0` |

---

## Installazione

```bash
composer require thecorps/laravel-cqrs
```

L'auto-discovery di Laravel registra automaticamente il `CqrsServiceProvider` e le facade `CommandBus` / `QueryBus`. Nessuna registrazione manuale necessaria.

### Pubblicare il file di configurazione

```bash
php artisan vendor:publish --tag=cqrs-config
```

Questo crea `config/cqrs.php` nella tua applicazione, dove puoi personalizzare la pipeline e le impostazioni di logging.

---

## Come funziona

```
CommandBus::dispatch(new CreateUserCommand(...))
    │
    ▼
LoggingBehavior           → logga "[CQRS] CreateUserCommand dispatched"
    │
    ▼
ValidationBehavior        → risolve #[CommandValidator] e chiama validate()
    │
    ▼
TransactionBehavior       → avvolge tutto il resto in DB::transaction()
    │
    ▼
HandlerExecutionBehavior  → risolve #[CommandHandler] e chiama handle()
    │
    ▼
CreateUserCommandHandler::handle(CreateUserCommand $command)
```

Le query seguono lo stesso pattern, ma saltano `LoggingBehavior` e `TransactionBehavior` per default.

---

## Utilizzo

### 1 — Command

Un command è un DTO immutabile che implementa `CommandInterface` e dichiara il suo handler tramite l'attributo `#[CommandHandler]`.

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

### 5 — Validator (opzionale)

I validator contengono regole di dominio che vanno oltre la validazione HTTP dell'input. Sono opt-in: senza `#[CommandValidator]`, il `ValidationBehavior` è un no-op.

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
                'email' => 'Questa email è già registrata.',
            ]);
        }
    }
}
```

---

## Configurazione

Dopo la pubblicazione, `config/cqrs.php` espone tre aree di configurazione.

### Composizione della pipeline

```php
'command_pipeline' => [
    LoggingBehavior::class,
    ValidationBehavior::class,
    TransactionBehavior::class,
    HandlerExecutionBehavior::class,  // deve essere sempre l'ultimo
],

'query_pipeline' => [
    ValidationBehavior::class,
    HandlerExecutionBehavior::class,  // deve essere sempre l'ultimo
],
```

Puoi aggiungere, rimuovere o riordinare i behavior liberamente. L'ordine nell'array è l'ordine di esecuzione.

### Behavior personalizzati

Qualsiasi classe che implementa `PipelineBehaviorInterface` può essere inserita nella pipeline. I behavior personalizzati vengono risolti dal container di Laravel, quindi supportano l'iniezione nel costruttore.

```php
use Closure;
use TheCorps\LaravelCqrs\Contracts\Interfaces\Pipeline\PipelineBehaviorInterface;

class RateLimitingBehavior implements PipelineBehaviorInterface
{
    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(object $command, Closure $next): mixed
    {
        // la tua logica trasversale qui
        return $next($command);
    }
}
```

```php
// config/cqrs.php
'command_pipeline' => [
    LoggingBehavior::class,
    RateLimitingBehavior::class,   // ← inserito
    ValidationBehavior::class,
    TransactionBehavior::class,
    HandlerExecutionBehavior::class,
],
```

### Logging

```php
'logging' => [
    'channel' => null,     // null = canale di log default di Laravel; oppure 'daily', 'slack', ecc.
    'level'   => 'debug',  // qualsiasi livello PSR-3: debug, info, notice, warning, error
],
```

---

## Struttura del package

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
│       │   ├── CommandInterface.php           ← implementa su ogni Command DTO
│       │   ├── CommandHandlerInterface.php    ← implementa su ogni CommandHandler
│       │   └── ValidatesCommandInterface.php  ← implementa su ogni CommandValidator
│       ├── Queries/
│       │   ├── QueryInterface.php             ← implementa su ogni Query DTO
│       │   ├── QueryHandlerInterface.php      ← implementa su ogni QueryHandler
│       │   └── ValidatesQueryInterface.php    ← implementa su ogni QueryValidator
│       └── Pipeline/
│           └── PipelineBehaviorInterface.php  ← implementa per behavior personalizzati
├── Pipeline/
│   ├── Behaviors/
│   │   ├── LoggingBehavior.php           ← logga dispatch e completamento
│   │   ├── ValidationBehavior.php        ← esegue il validator se dichiarato
│   │   ├── TransactionBehavior.php       ← avvolge in DB::transaction()
│   │   └── HandlerExecutionBehavior.php  ← risolve e chiama l'handler
│   ├── MetadataResolver.php  ← legge attributi PHP via reflection (cache statica)
│   └── Pipeline.php          ← compone ed esegue la catena di behavior
├── Bus/
│   ├── CommandBus.php  ← bus concreto registrato nel container
│   └── QueryBus.php
├── Facades/
│   ├── CommandBus.php  ← facade Laravel
│   └── QueryBus.php
└── CqrsServiceProvider.php  ← registra i bus, unisce config, pubblica i file
```

---

## Best practice

**Mantieni Command e Query come DTO immutabili.**
Usa proprietà `readonly` nel costruttore. Un command deve portare esattamente ciò di cui ha bisogno — nessun campo nullable opzionale che maschera dati mancanti.

**Separa la validazione HTTP dalla validazione di dominio.**
Usa `FormRequest` di Laravel per controllare formato, presenza e tipo dei campi. Usa `CommandValidator` per le regole di dominio: unicità, transizioni di stato, vincoli cross-aggregate.

```
FormRequest       → l'email è in formato valido? il campo è presente?
CommandValidator  → questa email è già registrata? questa subscription può essere messa in pausa?
```

**Gli handler devono essere orchestratori snelli.**
Mantieni la business logic in model, domain service o value object. L'unico compito dell'handler è coordinarli.

**Le query non devono mai scrivere.**
Se ti trovi a chiamare `save()` dentro un query handler, sposta quella logica in un command.

**`HandlerExecutionBehavior` deve essere sempre l'ultimo.**
Qualsiasi behavior inserito dopo di esso non verrà mai eseguito.

**Non aggiungere `TransactionBehavior` alla query pipeline.**
Avvolgere query read-only in transazioni è overhead inutile. La configurazione di default già riflette questo.
