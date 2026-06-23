<?php

namespace WebRegulate\LaravelAdministration\Classes\VersionHandler;

/**
 * Base class for a single version update definition.
 *
 * Each WRLA version that requires migration / breaking-change handling has its
 * own class in the Versions directory (eg. Version_0_1_001). The class declares
 * the version it represents and performs whatever steps are required: running
 * composer, mutating config files, asking the user questions, etc.
 *
 * Versions are discovered and run in ascending order by VersionHandler. The
 * version scheme uses three digits per group decimal (eg. 0.1.001, 0.1.002,
 * ..., 0.1.999, 0.2.000).
 */
abstract class VersionUpdate
{
    /**
     * The version this update upgrades the application to (eg. "0.1.001").
     */
    abstract public function version(): string;

    /**
     * A short human readable title describing the update.
     */
    abstract public function title(): string;

    /**
     * Perform the update. All output and questions must go through the given
     * context so the update works identically from the console and the web
     * dev-tools modal.
     *
     * Throw an exception to abort the update (the version will not be marked
     * as applied).
     */
    abstract public function run(VersionUpdateContext $context): void;

    /**
     * Helper: run `composer update` honouring the developer.composer.no_dev
     * config (the list of app environments that should update without dev
     * dependencies). Returns true on success.
     */
    protected function runComposerUpdate(VersionUpdateContext $context): bool
    {
        $noDevEnvironments = config('wr-laravel-administration.developer.composer.no_dev', ['production']);
        $useNoDev = in_array(app()->environment(), (array) $noDevEnvironments, true);

        $command = ['composer', 'update', '--no-interaction'];
        if ($useNoDev) {
            $command[] = '--no-dev';
        }

        $context->line('Running: ' . implode(' ', $command));
        $context->line('-------------------------------------');

        $success = $context->runProcess($command);

        if (! $success) {
            $context->error('composer update failed.');
        }

        return $success;
    }
}
