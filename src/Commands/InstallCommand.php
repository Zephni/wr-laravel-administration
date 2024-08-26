<?php

namespace WebRegulate\LaravelAdministration\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
            'User.stub',
            [],
            app_path('WRLA/User.php')
        );

        // If the user model was created, show a message
        if ($createdUserAt !== false) {
            $this->info(' - User model created successfully here: ' . $createdUserAt);
        } else {
            $this->warn(' - User model already exists at ' . WRLAHelper::removeBasePath(app_path('WRLA/User.php')) . '. To replace it delete the file and run again.');
        }

        // Create WRLASetup class
        $createdWRLASetupAt = WRLAHelper::generateFileFromStub(
            'WRLASetup.stub',
            [],
            app_path('WRLA/WRLASetup.php')
        );

        // If the WRLASetup class was created
        if ($createdWRLASetupAt !== false) {
            $this->info(' - WRLASetup class created successfully here: ' . $createdWRLASetupAt);
        } else {
            $this->warn(' - WRLASetup class already exists at ' . WRLAHelper::removeBasePath(app_path('WRLA/WRLASetup.php')) . '. To replace it delete the file and run again.');
        }

        // Create NotificationBase class
        $notificationBaseFile = WRLAHelper::generateFileFromStub(
            'NotificationExample.stub',
            [],
            app_path('NotificationDefinitions/NotificationExample.php')
        );

        // If the NotificationExample class was created
        if ($notificationBaseFile !== false) {
            $this->info(' - NotificationExample class created successfully here: ' . $notificationBaseFile);
        } else {
            $this->warn(' - NotificationExample class already exists at ' . WRLAHelper::removeBasePath(app_path('NotificationDefinitions/NotificationExample.php')) . '. To replace it delete the file and run again.');
        }

        // Success message
        $this->line('');
        $this->info('WRLA installed successfully.');

        // get database name and check if database connection already exists
        $databaseConnectionExists = false;
        $databaseName = 'null';
        try {
            $databaseName = DB::connection()->getPDO()
                ? DB::connection()->getDatabaseName()
                : 'null';
            $databaseConnectionExists = true;
        } catch (\Exception $e) {
            $databaseConnectionExists = false;
        }

        // Would you like to run the migrations, default to true
        $runMigrations = $this->confirm('Would you like to run the migrations'.($databaseConnectionExists ? " (Connected to $databaseName)" : "").'?', true);
        if ($runMigrations) {
            $this->call('migrate');
        }

        // If ran migrations or database connection exists, ask user if wants to create a master user
        if($runMigrations || $databaseConnectionExists) {
            // Check to see if there are any users exist in the database already
            $anyUsersExist = $databaseConnectionExists ? User::limit(1)->count() > 0 : false;

            // Ask if the user wants to create a default master user, default to true if no users exist
            if ($this->confirm('Would you like to create a master user?', !$anyUsersExist)) {
                // Run wrla:user command
                $this->call('wrla:user', ['master' => true]);
            }
        }

        // Show link to documentation
        $this->alert('Please visit ' . WRLAHelper::getDocumentationUrl() . ' for documentation.');

        // New line for separation
        $this->line('');

        return 1;
    }
}
