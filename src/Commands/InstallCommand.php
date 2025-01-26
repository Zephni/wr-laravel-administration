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
            '--provider' => 'WebRegulate\LaravelAdministration\WRLAServiceProvider',
            '--tag' => 'wrla-config',
        ]);

        // Publish assets
        $this->call('vendor:publish', [
            '--provider' => 'WebRegulate\LaravelAdministration\WRLAServiceProvider',
            '--tag' => 'wrla-assets',
        ]);

        // Publish models
        $this->call('vendor:publish', [
            '--provider' => 'WebRegulate\LaravelAdministration\WRLAServiceProvider',
            '--tag' => 'wrla-assets',
        ]);

        // First clear config cache
        $this->call('optimize:clear');

        // If .env DB_CONNECTION is not mysql, replace in config and UserData model file
        $envConnection = env('DB_CONNECTION', 'mysql');
        if($envConnection !== 'mysql') {
            // Config file
            $configFile = config_path('wr-laravel-administration.php');
            $configContents = file_get_contents($configFile);
            $configContents = str_replace("'connection' => 'mysql',", "'connection' => '$envConnection',", $configContents);
            file_put_contents($configFile, $configContents);

            // Show message
            $this->info(" - Changed 'wrla_user_data.connection' in config to '$envConnection'");
        }

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

        /* --- Add relationship to frontend user model --- */
        // Get current frontend user model (which uses config('wr-laravel-administration.models.user') namespace app path)
        // and add the wrlaUserData relationship to the end of the model
        $userModelPath = app_path(str(config('wr-laravel-administration.models.user'))->ltrim('\\App')->replace('\\', '/')->ltrim('/').'.php');
        $userModelContents = file_get_contents($userModelPath);

        // Check if file already has the relationship
        if (strpos($userModelContents, 'function wrlaUserData()') !== false) {
            $this->warn(' - wrlaUserData relationship already exists in '.config('wr-laravel-administration.models.user').' model. To replace it delete the relationship and run again.');
        } else {
            // Let user know we are about to add a relationship to the User model, we use confirmation to gurantree they are aware
            $confirm = $this->confirm('IMPORTANT: Adding wrlaUserData relationship to end of '.config('wr-laravel-administration.models.user').' model. This is nessesary for managing user\'s with WRLA', true);

            if($confirm) {
                // Find last } in file and add the relationship before it
                $lastBracePosition = strrpos($userModelContents, '}');
                $userModelContents = substr_replace($userModelContents, '
    /**
     * Get User data
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function wrlaUserData(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\WebRegulate\LaravelAdministration\Classes\WRLAHelper::getUserDataModelClass());
    }
', $lastBracePosition, 0);

            // Save the file
            file_put_contents($userModelPath, $userModelContents);

            // Show message
            $this->info(' - wrlaUserData relationship added to '.config('wr-laravel-administration.models.user').' model');
            }
        }
        /* --- /Add relationship to frontend user model --- */

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
