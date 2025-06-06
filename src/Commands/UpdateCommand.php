<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;
use WebRegulate\LaravelAdministration\Classes\VersionHandler\VersionHandler;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wrla:update';

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
        // Instantiate VersionHandler and run updates
        $versionHandler = new VersionHandler($this);
        $versionHandler->runUpdates();

        return 0;
    }
}