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
        // Publish config
        $this->call('vendor:publish', [
            '--provider' => \WebRegulate\LaravelAdministration\WRLAServiceProvider::class,
            '--tag' => 'wrla-config',
        ]);


        // Publish assets
        $this->call('vendor:publish', [
            '--provider' => \WebRegulate\LaravelAdministration\WRLAServiceProvider::class,
            '--tag' => 'wrla-assets',
        ]);

        // Publish log-viewer assets
        $this->call('log-viewer:publish');
        $this->call('vendor:publish', [
            '--tag' => 'log-viewer-config',
        ]);

        // Create UserData.php model in app/Models
        $envConnection = env('DB_CONNECTION', 'mysql');
        WRLAHelper::generateFileFromStub(
            'UserData.stub',
            [
                '{{ NAMESPACE }}' => 'App\Models',
                '{{ CONNECTION }}' => "'$envConnection'",
            ],
            app_path('Models/UserData.php')
        );

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

        // Create EmailTemplate manageable model
        $createdEmailTemplateAt = WRLAHelper::generateFileFromStub(
            'EmailTemplate.stub',
            [],
            app_path('WRLA/EmailTemplate.php')
        );

        // If the EmailTemplate model was created, show a message
        if ($createdEmailTemplateAt !== false) {
            $this->info(' - EmailTemplate model created successfully here: ' . $createdEmailTemplateAt);
        } else {
            $this->warn(' - EmailTemplate model already exists at ' . WRLAHelper::removeBasePath(app_path('WRLA/EmailTemplate.php')) . '. To replace it delete the file and run again.');
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

        // Create NotificationCustom class
        $notificationBaseFile = WRLAHelper::generateFileFromStub(
            'NotificationCustom.stub',
            [],
            app_path('WRLA/NotificationDefinitions/NotificationCustom.php')
        );

        // If the NotificationExample class was created
        if ($notificationBaseFile !== false) {
            $this->info(' - NotificationCustom class created successfully here: ' . $notificationBaseFile);
        } else {
            $this->warn(' - NotificationCustom class already exists at ' . WRLAHelper::removeBasePath(app_path('WRLA/NotificationDefinitions/NotificationCustom.php')) . '. To replace it delete the file and run again.');
        }

        // Create NotificationMail class
        $emailTemplateMailFile = WRLAHelper::generateFileFromStub(
            'EmailTemplateMail.stub',
            [],
            app_path('Mail/WRLA/EmailTemplateMail.php')
        );

        // If the NotificationMail class was created
        if ($emailTemplateMailFile !== false) {
            $this->info(' - EmailTemplateMail class created successfully here: ' . $emailTemplateMailFile);
        } else {
            $this->warn(' - EmailTemplateMail class already exists at ' . WRLAHelper::removeBasePath(app_path('Mail/WRLA/NotificationMail.php')) . '. To replace it delete the file and run again.');
        }

        // Create notification-mail.blade.php file
        $emailTemplateMailBladeFile = WRLAHelper::generateFileFromStub(
            'email-template-mail.blade.stub',
            [],
            resource_path('views/email/wrla/email-template-mail.blade.php')
        );

        // If the notification-mail.blade.php file was created
        if ($emailTemplateMailBladeFile !== false) {
            $this->info(' - email-template-mail.blade.php created successfully here: ' . $emailTemplateMailBladeFile);
        } else {
            $this->warn(' - email-template-mail.blade.php already exists at ' . WRLAHelper::removeBasePath(resource_path('views/email/wrla/email-template-mail.blade.php')) . '. To replace it delete the file and run again.');
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
        } catch (\Exception) {
            $databaseConnectionExists = false;
        }

        // Would you like to run the migrations, default to true
        $runMigrations = $this->confirm('Would you like to run the migrations'.($databaseConnectionExists ? " (Connected to $databaseName)" : "").'?', true);
        if ($runMigrations) {
            $this->call('migrate');
        }

        // Add symlink for storage (if doesn't already exist)
        if (!file_exists(public_path('storage'))) {
            if ($this->confirm('Would you like to create a symlink for the storage folder?', true)) {
                $this->call('storage:link');
            }
        } else {
            $this->warn(' - storage symlink already exists');
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
