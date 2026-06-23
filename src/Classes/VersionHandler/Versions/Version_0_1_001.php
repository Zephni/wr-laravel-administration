<?php

namespace WebRegulate\LaravelAdministration\Classes\VersionHandler\Versions;

use WebRegulate\LaravelAdministration\Classes\VersionHandler\VersionUpdate;
use WebRegulate\LaravelAdministration\Classes\VersionHandler\VersionUpdateContext;

/**
 * Version 0.1.001
 *
 * - Runs composer update (honouring developer.composer.no_dev per environment).
 * - Migrates the published config from the legacy `enable_developer_tools`
 *   callback to the new grouped `developer` structure:
 *
 *       'developer' => [
 *           'enable' => fn($wrlaUserData) => ...,   // preserved from the old key
 *           'composer' => [
 *               'no_dev' => ['production'],
 *           ],
 *       ],
 */
class Version_0_1_001 extends VersionUpdate
{
    public function version(): string
    {
        return '0.1.001';
    }

    public function title(): string
    {
        return 'Composer update & developer config restructure';
    }

    public function run(VersionUpdateContext $context): void
    {
        // 1. Pull the latest package code via composer
        $context->line('Step 1/2: Updating composer dependencies...');

        if (! $this->runComposerUpdate($context)) {
            throw new \RuntimeException('composer update did not complete successfully.');
        }

        $context->line('');

        // 2. Migrate the published config to the new developer group structure
        $context->line('Step 2/2: Migrating developer tools configuration...');
        $this->migrateDeveloperConfig($context);
    }

    /**
     * Migrate the user's published config file from the legacy
     * `enable_developer_tools` key to the new grouped `developer` structure,
     * preserving whatever callback the user already had configured.
     */
    protected function migrateDeveloperConfig(VersionUpdateContext $context): void
    {
        $configPath = config_path('wr-laravel-administration.php');

        if (! file_exists($configPath)) {
            $context->warn(' - Published config not found, skipping config migration.');
            return;
        }

        $contents = file_get_contents($configPath);

        // Idempotent: if the legacy key is gone, assume the migration already ran
        if (! str_contains($contents, "'enable_developer_tools'")) {
            $context->info(' - Developer config already migrated, nothing to do.');
            return;
        }

        // Match the legacy key (and an optional preceding "developer tools" comment),
        // capturing the indentation and the existing callback expression so it is preserved.
        $pattern = "/^([ \t]*)(?:\/\/[^\n]*developer tools[^\n]*\r?\n[ \t]*)?'enable_developer_tools'\s*=>\s*(.+)$/mi";

        $updated = preg_replace_callback($pattern, function ($matches) {
            $indent = $matches[1];

            // Strip a trailing line comment, then trailing comma/whitespace to isolate the expression
            $expression = preg_replace('/\s*\/\/.*$/', '', $matches[2]);
            $expression = rtrim(trim($expression), ',');
            $expression = trim($expression);

            return $this->buildDeveloperConfigBlock($indent, $expression);
        }, $contents, 1, $count);

        if (! $count || $updated === null) {
            $context->warn(' - Could not locate the enable_developer_tools key, config left unchanged.');
            return;
        }

        file_put_contents($configPath, $updated);
        $context->info(' - Developer tools configuration migrated to the new developer group.');
    }

    /**
     * Build the replacement `developer` config block, preserving the existing
     * enable expression and base indentation.
     */
    protected function buildDeveloperConfigBlock(string $indent, string $enableExpression): string
    {
        $eol = PHP_EOL;

        return "{$indent}// Developer tooling configuration{$eol}"
            . "{$indent}'developer' => [{$eol}"
            . "{$indent}    // Callback for enabling developer tools, takes wrlaUserData and must return boolean.{$eol}"
            . "{$indent}    'enable' => {$enableExpression}, // EG. use: \$wrlaUserData->isMaster() to enable for master users only{$eol}"
            . "{$indent}{$eol}"
            . "{$indent}    // Composer behaviour for the wrla:update command{$eol}"
            . "{$indent}    'composer' => [{$eol}"
            . "{$indent}        // App environments that should run `composer update --no-dev`{$eol}"
            . "{$indent}        'no_dev' => ['production'],{$eol}"
            . "{$indent}    ],{$eol}"
            . "{$indent}{$eol}"
            . "{$indent}    // How the web dev-tools \"Update WRLA\" modal runs updates: 'live' or 'blocking'{$eol}"
            . "{$indent}    'update' => [{$eol}"
            . "{$indent}        'mode' => 'live',{$eol}"
            . "{$indent}    ],{$eol}"
            . "{$indent}],";
    }
}
