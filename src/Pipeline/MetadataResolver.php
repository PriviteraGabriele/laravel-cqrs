<?php

namespace TheCorps\LaravelCqrs\Pipeline;

use ReflectionClass;
use RuntimeException;

/**
 * Resolves the handler and validator associated with a command or query via PHP attributes.
 *
 * Results are cached statically for the lifetime of the request to avoid
 * repeated reflection on the same class.
 */
class MetadataResolver
{
    /** @var array<string, array{handler: string, validator: string|null}> */
    private static array $cache = [];

    /**
     * Creates a new resolver instance.
     *
     * @param string      $handlerAttributeClass   Fully qualified class name of the handler attribute to look for.
     * @param string|null $validatorAttributeClass Fully qualified class name of the validator attribute to look for.
     */
    public function __construct(
        private readonly string $handlerAttributeClass,
        private readonly ?string $validatorAttributeClass = null,
    ) {}

    /**
     * Returns the handler class name associated with the given command or query.
     *
     * @param object $command The command or query to resolve.
     *
     * @return string
     */
    public function getHandlerClass(object $command): string
    {
        return $this->resolve($command)['handler'];
    }

    /**
     * Returns the validator class name associated with the given command or query, or null if none.
     *
     * @param object $command The command or query to resolve.
     *
     * @return string|null
     */
    public function getValidatorClass(object $command): ?string
    {
        return $this->resolve($command)['validator'];
    }

    /** @return array{handler: string, validator: string|null} */
    private function resolve(object $command): array
    {
        $cacheKey = get_class($command) . '|' . $this->handlerAttributeClass;

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $reflection = new ReflectionClass($command);

        $handlerClass = null;
        $validatorClass = null;

        $handlerAttrs = $reflection->getAttributes($this->handlerAttributeClass);

        if ($handlerAttrs !== []) {
            $handlerClass = $handlerAttrs[0]->newInstance()->handlerClass;
        }

        if ($this->validatorAttributeClass !== null) {
            $validatorAttrs = $reflection->getAttributes($this->validatorAttributeClass);

            if ($validatorAttrs !== []) {
                $validatorClass = $validatorAttrs[0]->newInstance()->validatorClass;
            }
        }

        if ($handlerClass === null) {
            throw new RuntimeException(
                'No #[' . class_basename($this->handlerAttributeClass) . '] attribute found on [' . get_class($command) . '].'
            );
        }

        return self::$cache[$cacheKey] = [
            'handler' => $handlerClass,
            'validator' => $validatorClass,
        ];
    }
}
