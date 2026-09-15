<?php

namespace TheCorps\LaravelCqrs\Console;

use Illuminate\Console\Command;
use TheCorps\LaravelCqrs\Support\AgentRulesInstaller;

/**
 * Installs the Laravel CQRS instructions used by supported coding agents.
 */
class InstallAgentRulesCommand extends Command
{
    /** @var string */
    protected $signature = 'cqrs:install-agent-rules';

    /** @var string */
    protected $description = 'Install Laravel CQRS rules for Codex and Claude Code.';

    /**
     * Installs or refreshes the managed agent instruction files.
     */
    public function handle(AgentRulesInstaller $installer): int
    {
        $result = $installer->install($this->laravel->basePath());

        if ($result->hasConflicts()) {
            foreach ($result->conflicts() as $path) {
                $this->error("Refusing to overwrite unmanaged skill: {$path}");
            }

            $this->line('Remove or rename the conflicting skill, then run this command again.');

            return self::FAILURE;
        }

        foreach ($result->writtenFiles() as $path) {
            $this->info("Installed Laravel CQRS agent rules: {$path}");
        }

        return self::SUCCESS;
    }
}
