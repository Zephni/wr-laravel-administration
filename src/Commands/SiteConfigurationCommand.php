<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class SiteConfigurationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wrla:site-configuration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the optional WRLA site configuration table, model and manageable model';

    /**
     * The base table name used by the site configuration feature.
     */
    protected const TABLE_NAME = 'wrla_site_configurations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->line('');
        $this->info('Installing the WRLA site configuration feature...');
        $this->line('');

        $this->generateMigration();
        $this->generateModel();
        $this->generateManageableModel();

        $this->promptRunMigrations();

        $this->line('');
        $this->info('🥳 Site configuration installed. Manage entries under the "Site Configuration" navigation item.');
        $this->line('');

        return 1;
    }

    /**
     * Generate the create_wrla_site_configurations_table migration, unless one
     * already exists.
     */
    protected function generateMigration(): void
    {
        // Check whether a matching migration already exists
        foreach (File::files(database_path('migrations')) as $file) {
            if (str($file->getFilename())->contains('create_'.static::TABLE_NAME.'_table')) {
                $this->warn(' - Migration already exists at '.WRLAHelper::removeBasePath($file->getPathname()).'. Skipping.');

                return;
            }
        }

        $timestamp = date('Y_m_d_His');
        $destination = database_path('migrations/'.$timestamp.'_create_'.static::TABLE_NAME.'_table.php');

        $createdAt = WRLAHelper::generateFileFromStub(
            'SiteConfigurationMigration.stub',
            [],
            $destination
        );

        $this->info(' - Migration created successfully here: '.$createdAt);
    }

    /**
     * Generate the App\Models\SiteConfiguration model.
     */
    protected function generateModel(): void
    {
        $destination = app_path('Models/SiteConfiguration.php');
        $forceOverwrite = $this->confirmOverwriteIfExists($destination, 'SiteConfiguration model');

        if ($forceOverwrite === null) {
            return;
        }

        $createdAt = WRLAHelper::generateFileFromStub(
            'SiteConfigurationModel.stub',
            ['{{ NAMESPACE }}' => 'App\\Models'],
            $destination,
            $forceOverwrite
        );

        $this->info(' - SiteConfiguration model created successfully here: '.($createdAt !== false ? $createdAt : WRLAHelper::removeBasePath($destination)));
    }

    /**
     * Generate the App\WRLA\SiteConfiguration manageable model.
     */
    protected function generateManageableModel(): void
    {
        $destination = app_path('WRLA/SiteConfiguration.php');
        $forceOverwrite = $this->confirmOverwriteIfExists($destination, 'SiteConfiguration manageable model');

        if ($forceOverwrite === null) {
            return;
        }

        $createdAt = WRLAHelper::generateFileFromStub(
            'SiteConfiguration.stub',
            ['{{ NAMESPACE }}' => 'App\\WRLA'],
            $destination,
            $forceOverwrite
        );

        $this->info(' - SiteConfiguration manageable model created successfully here: '.($createdAt !== false ? $createdAt : WRLAHelper::removeBasePath($destination)));
    }

    /**
     * Decide whether to (over)write a generated file.
     *
     * @return bool|null True/false to write (with/without overwrite), or null to skip.
     */
    protected function confirmOverwriteIfExists(string $destination, string $label): ?bool
    {
        if (! File::exists($destination)) {
            return false;
        }

        if ($this->confirm("The $label already exists at ".WRLAHelper::removeBasePath($destination).'. Overwrite it?', false)) {
            return true;
        }

        $this->warn(" - $label skipped (already exists).");

        return null;
    }

    /**
     * Prompt the user to run the migrations.
     */
    protected function promptRunMigrations(): void
    {
        $this->line('');

        if ($this->confirm('Would you like to run the migrations now?', true)) {
            $this->call('migrate');
        }
    }
}
