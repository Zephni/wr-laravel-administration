<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Facades\File;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wrla:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the WRLA package';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Publish the config file
        $this->call('vendor:publish', [
            '--provider' => 'WebRegulate\LaravelAdministration\WRLAServiceProvider',
            '--tag' => 'wrla-config',
        ]);

        // Publish the assets
        $this->call('vendor:publish', [
            '--provider' => 'WebRegulate\LaravelAdministration\WRLAServiceProvider',
            '--tag' => 'wrla-assets',
        ]);

        // Create user manageable model
        $createdUserAt = WRLAHelper::generateFileFromStub(
            'ManageableModel.stub',
            CreateManageableModelCommand::getStubVariables('User', 'fa fa-user'),
            app_path('WRLA/User.php')
        );

        // Success message
        $this->info('WRLA installed successfully.');

        // If the user model was created, show a message
        if ($createdUserAt !== false) {
            $this->info('User model created successfully here: ' . $createdUserAt);
        } else {
            $this->warn('User model already exists at ' . WRLAHelper::forwardSlashPath(str_replace(base_path(), '', app_path('WRLA/User.php'))) . '. If you want to recreate it, delete the file and run the command again.');
        }

        // New line for separation
        $this->line('');
    }
}
