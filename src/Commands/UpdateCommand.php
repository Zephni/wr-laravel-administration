<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;
use WebRegulate\LaravelAdministration\Classes\VersionHandler\VersionHandler;
use WebRegulate\LaravelAdministration\Classes\VersionHandler\ConsoleVersionUpdateContext;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wrla:update {--composer-only : Only run composer update (and optimize:clear), skipping version migrations.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'After updating this package via composer, run this command to check, update, or guide you through any changes / breaking changes.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $versionHandler = new VersionHandler(new ConsoleVersionUpdateContext($this));

        if ($this->option('composer-only')) {
            if ($versionHandler->runComposerUpdate()) {
                $versionHandler->runOptimizeClear();
            }

            return 0;
        }

        $versionHandler->runUpdates();

        return 0;
    }
}