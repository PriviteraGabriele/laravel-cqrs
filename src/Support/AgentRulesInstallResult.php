<?php

namespace TheCorps\LaravelCqrs\Support;

/**
 * Describes the outcome of an agent rules installation attempt.
 */
final class AgentRulesInstallResult
{
    /**
     * @param list<string> $writtenFiles
     * @param list<string> $conflicts
     */
    public function __construct(
        private readonly array $writtenFiles,
        private readonly array $conflicts,
    ) {}

    /** @return list<string> */
    public function writtenFiles(): array
    {
        return $this->writtenFiles;
    }

    /** @return list<string> */
    public function conflicts(): array
    {
        return $this->conflicts;
    }

    public function hasConflicts(): bool
    {
        return $this->conflicts !== [];
    }
}
