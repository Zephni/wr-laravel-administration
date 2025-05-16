<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class RebuildUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wrla:rebuild-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replace the \App\WRLA\User.php file with it\'s default stub.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Create user manageable model
        $createdUserAt = WRLAHelper::generateFileFromStub(
            'User.stub',
            [],
            app_path('WRLA/User.php'),
            true
        );

        // If the user model was created, show a message
        if ($createdUserAt !== false) {
            $this->info('User model created/replaced successfully here: '.$createdUserAt);
        } else {
            $this->warn('Creating user model failed.');
        }

        return 1;
    }
}
