<?php

namespace TheCorps\LaravelCqrs\Tests\Feature;

use Illuminate\Console\Command;
use TheCorps\LaravelCqrs\Tests\TestCase;

class InstallAgentRulesCommandTest extends TestCase
{
    private string $projectPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectPath = sys_get_temp_dir() . '/laravel-cqrs-agent-rules-' . uniqid('', true);
        mkdir($this->projectPath, 0755, true);
        $this->app->setBasePath($this->projectPath);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->projectPath);

        parent::tearDown();
    }

    public function test_it_installs_agent_rules_for_a_clean_project(): void
    {
        $this->artisan('cqrs:install-agent-rules')->assertExitCode(Command::SUCCESS);

        $this->assertFileExists($this->projectPath . '/AGENTS.md');
        $this->assertFileExists($this->projectPath . '/CLAUDE.md');
        $this->assertFileExists($this->projectPath . '/.agents/skills/laravel-cqrs/SKILL.md');
        $this->assertFileExists($this->projectPath . '/.claude/skills/laravel-cqrs/SKILL.md');

        $skill = file_get_contents($this->projectPath . '/.agents/skills/laravel-cqrs/SKILL.md');

        $this->assertStringContainsString('Use a command for every state change.', $skill);
        $this->assertStringContainsString('A query handler must never create, update, delete, save', $skill);
        $this->assertStringContainsString('HandlerExecutionBehavior` must be last', $skill);
        $this->assertStringContainsString('app/Domain/{Feature}/', $skill);
    }

    public function test_it_preserves_existing_root_instructions_and_updates_its_own_block(): void
    {
        file_put_contents($this->projectPath . '/AGENTS.md', "# Existing rules\\nKeep this instruction.\\n");
        file_put_contents($this->projectPath . '/CLAUDE.md', "# Claude rules\\nKeep this too.\\n");

        $this->artisan('cqrs:install-agent-rules')->assertExitCode(Command::SUCCESS);

        $firstInstall = file_get_contents($this->projectPath . '/AGENTS.md');

        $this->artisan('cqrs:install-agent-rules')->assertExitCode(Command::SUCCESS);

        $this->assertStringContainsString('Keep this instruction.', $firstInstall);
        $this->assertStringContainsString('Keep this too.', file_get_contents($this->projectPath . '/CLAUDE.md'));
        $this->assertSame($firstInstall, file_get_contents($this->projectPath . '/AGENTS.md'));
        $this->assertSame(1, substr_count($firstInstall, '<!-- laravel-cqrs-agent-rules:start -->'));
    }

    public function test_it_refuses_to_overwrite_an_unmanaged_skill_without_changing_root_files(): void
    {
        $path = $this->projectPath . '/.claude/skills/laravel-cqrs';
        mkdir($path, 0755, true);
        file_put_contents($path . '/SKILL.md', "---\\ndescription: A custom skill\\n---\\n");

        $this->artisan('cqrs:install-agent-rules')->assertExitCode(Command::FAILURE);

        $this->assertFileDoesNotExist($this->projectPath . '/AGENTS.md');
        $this->assertFileDoesNotExist($this->projectPath . '/CLAUDE.md');
        $this->assertFileDoesNotExist($this->projectPath . '/.agents/skills/laravel-cqrs/SKILL.md');
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $path . '/' . $entry;

            if (is_dir($entryPath)) {
                $this->deleteDirectory($entryPath);
            } else {
                unlink($entryPath);
            }
        }

        rmdir($path);
    }
}
