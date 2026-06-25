<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
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
        $this->promptRunMigrations();
        $this->generateModel();
        $this->generateManageableModel();
        $this->registerNavigationItem();
        
        
        $this->line('');
        $this->info('🥳 Site configuration installed. Manage entries under the "Site Configuration" navigation item.');
        $this->line('');

        return 1;
    }

    /**
     * Generate the create_wrla_site_configurations_table migration, unless one
     * already exists on disk (avoids creating a duplicate migration file).
     */
    protected function generateMigration(): void
    {
        // Don't create a duplicate migration file if one is already present.
        foreach (File::files(database_path('migrations')) as $file) {
            if (str($file->getFilename())->contains('create_'.static::TABLE_NAME.'_table')) {
                $this->info(' - Migration file already present: '.WRLAHelper::removeBasePath($file->getPathname()));

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
     * Register an explicit Site Configuration navigation item in the
     * application's WRLASettings::buildNavigation(). Inserted directly after the
     * Dashboard item by default (falling back to before the manageable models
     * import) so it sits near the top of the navigation. Idempotent.
     */
    protected function registerNavigationItem(): void
    {
        $settingsPath = app_path('WRLA/WRLASettings.php');
        $manualHint = 'Add \App\WRLA\SiteConfiguration::getNavigationItem() to buildNavigation() in WRLASettings.php manually.';

        if (! File::exists($settingsPath)) {
            $this->warn(' - Could not find WRLASettings.php. '.$manualHint);

            return;
        }

        $contents = File::get($settingsPath);

        // Idempotent: skip if the item is already referenced.
        if (str_contains($contents, 'SiteConfiguration::getNavigationItem')) {
            $this->warn(' - Site Configuration navigation item already present in WRLASettings.php. Skipping.');

            return;
        }

        // Prefer to insert immediately after the Dashboard navigation item, so
        // the Site Configuration item sits just below it. Fall back to inserting
        // before the bulk manageable models import.
        [$lineStart, $indent] = $this->resolveNavigationInsertionPoint($contents);

        if ($lineStart === null) {
            $this->warn(' - Could not locate a navigation anchor in WRLASettings.php. '.$manualHint);

            return;
        }

        if (! $this->confirm('Add the Site Configuration navigation item below the Dashboard item in WRLASettings.php?', true)) {
            $this->warn(' - Navigation item not added. '.$manualHint);

            return;
        }

        $insertion = $indent."// Site Configuration (added by wrla:site-configuration). Hidden until the table exists.\n"
            .$indent."\\App\\WRLA\\SiteConfiguration::getNavigationItem(),\n\n";

        $contents = substr($contents, 0, $lineStart).$insertion.substr($contents, $lineStart);
        File::put($settingsPath, $contents);

        $this->info(' - Site Configuration navigation item added to '.WRLAHelper::removeBasePath($settingsPath).'.');
    }

    /**
     * Resolve where to insert the navigation item. Returns [int $offset, string
     * $indent] for the insertion point, or [null, ''] when no anchor is found.
     *
     * Prefers the line after the Dashboard item; otherwise the line of the bulk
     * manageable models import.
     *
     * @return array{0: ?int, 1: string}
     */
    protected function resolveNavigationInsertionPoint(string $contents): array
    {
        // Preferred: insert on the line after the Dashboard navigation item.
        if (($dashboardPos = strpos($contents, 'wrla.dashboard')) !== false) {
            // End of the Dashboard statement's line.
            $lineEnd = strpos($contents, "\n", $dashboardPos);

            if ($lineEnd !== false) {
                $lineStartPos = strrpos(substr($contents, 0, $dashboardPos), "\n");
                $lineStartPos = $lineStartPos === false ? 0 : $lineStartPos + 1;
                $line = substr($contents, $lineStartPos, $dashboardPos - $lineStartPos);
                $indent = substr($line, 0, strlen($line) - strlen(ltrim($line)));

                return [$lineEnd + 1, $indent];
            }
        }

        // Fallback: insert before the bulk manageable models import.
        if (($anchorPos = strpos($contents, 'NavigationItem::makeManageableModels(')) !== false) {
            $lineStartPos = strrpos(substr($contents, 0, $anchorPos), "\n");
            $lineStartPos = $lineStartPos === false ? 0 : $lineStartPos + 1;
            $indent = substr($contents, $lineStartPos, $anchorPos - $lineStartPos);

            return [$lineStartPos, $indent];
        }

        return [null, ''];
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
     *
     * Bases its behaviour on whether the table actually exists rather than on
     * whether a migration file is present:
     *  - Table missing: show a dry run of the SQL, then offer to run (default yes).
     *  - Table exists: warn that it has likely already been run, and offer to run
     *    again anyway (default no). The migration itself is idempotent.
     */
    protected function promptRunMigrations(): void
    {
        $this->line('');

        if ($this->tableExists()) {
            $this->warn("The '".static::TABLE_NAME."' table already exists, so this migration has most likely already been run.");

            if ($this->confirm('Run the migration again anyway?', false)) {
                $this->call('migrate');
            }

            return;
        }

        // Show a dry run of the pending migrations so the user can review the
        // SQL that would be executed before committing to running them.
        $this->info('Previewing pending migrations (dry run)...');
        $this->line('');
        $this->call('migrate', ['--pretend' => true]);
        $this->line('');

        if ($this->confirm('Would you like to run the migrations now?', true)) {
            $this->call('migrate');
        }
    }

    /**
     * Whether the site configuration table currently exists. Returns false if
     * the database connection is unavailable.
     */
    protected function tableExists(): bool
    {
        try {
            return Schema::hasTable(static::TABLE_NAME);
        } catch (\Throwable) {
            return false;
        }
    }
}
