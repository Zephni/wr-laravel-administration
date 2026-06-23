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
     * Ensure the user's published config file contains the new grouped
     * `developer` structure. Handles three scenarios:
     *
     *  1. The new `developer` block already exists  -> nothing to do.
     *  2. The legacy `enable_developer_tools` key exists -> replace it with the
     *     new block, preserving whatever callback the user already had.
     *  3. Neither exists (eg. an app that never published the dev-tools key) ->
     *     insert the new block at a sensible location with default values.
     */
    protected function migrateDeveloperConfig(VersionUpdateContext $context): void
    {
        $configPath = config_path('wr-laravel-administration.php');

        if (! file_exists($configPath)) {
            $context->warn(' - Published config not found, skipping config migration.');
            return;
        }

        $contents = file_get_contents($configPath);

        // 1. Idempotent: if the new developer group already exists, there is nothing to do
        if ($this->hasDeveloperBlock($contents)) {
            $context->info(' - Developer config already present, nothing to do.');
            return;
        }

        // 2. Legacy key present -> replace it (preserving the existing enable expression)
        if (str_contains($contents, "'enable_developer_tools'")) {
            $this->replaceLegacyKey($context, $configPath, $contents);
            return;
        }

        // 3. Neither present -> insert the new block with default values
        $this->insertDeveloperBlock($context, $configPath, $contents);
    }

    /**
     * Determine whether the config already declares the new grouped
     * `'developer' => [ ... ]` structure.
     */
    protected function hasDeveloperBlock(string $contents): bool
    {
        return (bool) preg_match("/^[ \t]*'developer'\s*=>\s*\[/m", $contents);
    }

    /**
     * Replace the legacy `enable_developer_tools` key with the new developer
     * group, preserving the existing enable callback expression.
     */
    protected function replaceLegacyKey(VersionUpdateContext $context, string $configPath, string $contents): void
    {
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
     * Insert the new developer group into a config that has neither the legacy
     * key nor the new block. The block is placed immediately before the first
     * available anchor so it lands in a sensible spot.
     */
    protected function insertDeveloperBlock(VersionUpdateContext $context, string $configPath, string $contents): void
    {
        // Prioritised anchors to insert before. Each captures the indentation and
        // an optional preceding comment line so the new block sits neatly above it.
        $anchors = [
            // Before the documentation configuration (its canonical neighbour)
            "/^([ \t]*)(\/\/ Documentation configuration\r?\n[ \t]*)?'documentation'\s*=>/m",
            // Otherwise before the GENERAL CONFIGURATION section header
            "/^([ \t]*)(\/\*-+\r?\n[ \t]*GENERAL CONFIGURATION)/m",
        ];

        foreach ($anchors as $pattern) {
            $updated = preg_replace_callback($pattern, function ($matches) {
                $indent = $matches[1];
                $block = $this->buildDeveloperConfigBlock($indent, 'fn($wrlaUserData) => false');

                // Prepend the new block (and a blank line) before the matched anchor,
                // re-emitting the full match so nothing is lost.
                return $block . PHP_EOL . PHP_EOL . $matches[0];
            }, $contents, 1, $count);

            if ($count && $updated !== null) {
                file_put_contents($configPath, $updated);
                $context->info(' - Developer tooling configuration added to config.');
                return;
            }
        }

        $context->warn(' - Could not find a location to insert the developer config, config left unchanged.');
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
            . "{$indent}    // How the web dev-tools \"Update WRLA\" modal runs updates:{$eol}"
            . "{$indent}    //  'live'     - run in the background and stream the console output to the modal as it happens{$eol}"
            . "{$indent}    //  'blocking' - run synchronously and show the full output once finished{$eol}"
            . "{$indent}    'update' => [{$eol}"
            . "{$indent}        'mode' => 'live',{$eol}"
            . "{$indent}    ],{$eol}"
            . "{$indent}],";
    }
}
