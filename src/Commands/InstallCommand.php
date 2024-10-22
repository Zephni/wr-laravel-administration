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

        // Create WRLASettings class
        $createdWRLASettingsAt = WRLAHelper::generateFileFromStub(
            'WRLASettings.stub',
            [],
            app_path('WRLA/WRLASettings.php')
        );

        // If the WRLASettings class was created
        if ($createdWRLASettingsAt !== false) {
            $this->info(' - WRLASettings class created successfully here: ' . $createdWRLASettingsAt);
        } else {
            $this->warn(' - WRLASettings class already exists at ' . WRLAHelper::removeBasePath(app_path('WRLA/WRLASettings.php')) . '. To replace it delete the file and run again.');
        }

        // Create NotificationBase class
        $notificationBaseFile = WRLAHelper::generateFileFromStub(
            'NotificationExample.stub',
            [],
            app_path('WRLA/NotificationDefinitions/NotificationExample.php')
        );

        // If the NotificationExample class was created
        if ($notificationBaseFile !== false) {
            $this->info(' - NotificationExample class created successfully here: ' . $notificationBaseFile);
        } else {
            $this->warn(' - NotificationExample class already exists at ' . WRLAHelper::removeBasePath(app_path('WRLA/NotificationDefinitions/NotificationExample.php')) . '. To replace it delete the file and run again.');
        }

        // Create NotificationMail class
        $notificationMailFile = WRLAHelper::generateFileFromStub(
            'NotificationMail.stub',
            [],
            app_path('Mail/WRLA/NotificationMail.php')
        );

        // If the NotificationMail class was created
        if ($notificationMailFile !== false) {
            $this->info(' - NotificationMail class created successfully here: ' . $notificationMailFile);
        } else {
            $this->warn(' - NotificationMail class already exists at ' . WRLAHelper::removeBasePath(app_path('Mail/WRLA/NotificationMail.php')) . '. To replace it delete the file and run again.');
        }

        // Create notification-mail.blade.php file
        $notificationMailBladeFile = WRLAHelper::generateFileFromStub(
            'notification-mail.blade.stub',
            [],
            resource_path('views/email/wrla/notification-mail.blade.php')
        );

        // If the notification-mail.blade.php file was created
        if ($notificationMailBladeFile !== false) {
            $this->info(' - notification-mail.blade.php created successfully here: ' . $notificationMailBladeFile);
        } else {
            $this->warn(' - notification-mail.blade.php already exists at ' . WRLAHelper::removeBasePath(resource_path('views/email/wrla/notification-mail.blade.php')) . '. To replace it delete the file and run again.');
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
