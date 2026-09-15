<?php

namespace TheCorps\LaravelCqrs\Support;

use RuntimeException;

/**
 * Installs managed agent instructions into a Laravel application root.
 */
final class AgentRulesInstaller
{
    private const ROOT_BLOCK_START = '<!-- laravel-cqrs-agent-rules:start -->';
    private const ROOT_BLOCK_END = '<!-- laravel-cqrs-agent-rules:end -->';
    private const SKILL_MARKER = '<!-- laravel-cqrs-managed-skill -->';

    /**
     * Installs the root instruction blocks and both project-local skill copies.
     */
    public function install(string $basePath): AgentRulesInstallResult
    {
        $skillPaths = [
            $basePath . '/.agents/skills/laravel-cqrs/SKILL.md',
            $basePath . '/.claude/skills/laravel-cqrs/SKILL.md',
        ];

        $conflicts = $this->findSkillConflicts($skillPaths);

        if ($conflicts !== []) {
            return new AgentRulesInstallResult([], $conflicts);
        }

        $writtenFiles = [];

        foreach ([
            'AGENTS.md' => '.agents/skills/laravel-cqrs/SKILL.md',
            'CLAUDE.md' => '.claude/skills/laravel-cqrs/SKILL.md',
        ] as $filename => $skillPath) {
            $path = $basePath . '/' . $filename;
            $this->writeFile($path, $this->withManagedRootBlock($this->readFile($path), $skillPath));
            $writtenFiles[] = $path;
        }

        $skill = $this->skillContents();

        foreach ($skillPaths as $path) {
            $this->writeFile($path, $skill);
            $writtenFiles[] = $path;
        }

        return new AgentRulesInstallResult($writtenFiles, []);
    }

    /**
     * @param list<string> $skillPaths
     *
     * @return list<string>
     */
    private function findSkillConflicts(array $skillPaths): array
    {
        $conflicts = [];

        foreach ($skillPaths as $path) {
            if (is_link($path) || is_dir($path)) {
                $conflicts[] = $path;

                continue;
            }

            if (is_file($path) && !str_contains($this->readFile($path), self::SKILL_MARKER)) {
                $conflicts[] = $path;
            }
        }

        return $conflicts;
    }

    private function withManagedRootBlock(string $contents, string $skillPath): string
    {
        $block = implode(PHP_EOL, [
            self::ROOT_BLOCK_START,
            '## Laravel CQRS agent rules',
            '',
            'For every task that creates or changes CQRS code, read and follow `' . $skillPath . '` before editing.',
            'The rules in that skill are mandatory for commands, queries, handlers, validators, dispatching, and pipeline configuration.',
            self::ROOT_BLOCK_END,
        ]);

        $pattern = '/' . preg_quote(self::ROOT_BLOCK_START, '/') . '.*?' . preg_quote(self::ROOT_BLOCK_END, '/') . '\\s*/s';

        if (preg_match($pattern, $contents) === 1) {
            return (string) preg_replace($pattern, $block . PHP_EOL, $contents);
        }

        return rtrim($contents) === ''
            ? $block . PHP_EOL
            : rtrim($contents) . PHP_EOL . PHP_EOL . $block . PHP_EOL;
    }

    private function skillContents(): string
    {
        $path = dirname(__DIR__, 2) . '/resources/skills/laravel-cqrs/SKILL.md';

        if (!is_file($path)) {
            throw new RuntimeException("Unable to read required skill resource [{$path}].");
        }

        return $this->readFile($path);
    }

    private function readFile(string $path): string
    {
        if (!is_file($path)) {
            return '';
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read [{$path}].");
        }

        return $contents;
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create [{$directory}].");
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException("Unable to write [{$path}].");
        }
    }
}
