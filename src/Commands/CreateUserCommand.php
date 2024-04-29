<?php

namespace WebRegulate\LaravelAdministration\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Signature can optionally have master flag
    protected $signature = 'wrla:user {master?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a WRLA user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Check if master flag passed
        $master = $this->argument('master') !== null;

        // If master isn't passed, check if the user wants to create a master user
        if (!$master) {
            $master = $this->confirm('Do you want to create a master user?', false);
        }

        // If not master, then ask if the user should be an admin
        if (!$master) {
            $admin = $this->confirm('Should the user be an admin?', false);
        } else {
            $admin = true;
        }

        // User text
        $userText = str($master ? 'Master user' : 'User');

        // Ask for name
        $name = $this->ask('Enter the name for the '.$userText->lower(), $userText);

        $emailSuccess = false;
        while($emailSuccess === false) {
            // Ask for the email
            $email = $this->ask('Enter the email for the user', $master ? 'master@domain.com' : 'user@domain.com');

            // Check if user already exists
            $user = User::where('email', $email)->first();

            if ($user == null && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                $emailSuccess = true;
            } else {
                $this->error('Invalid email address or email already exists. Please try again.');
            }
        }

        // Generate a random password for the default, and ask user to set
        $defaultPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
        $password = $this->ask('Enter the password for the '.$userText->lower(), $defaultPassword);

        // Create a dummy user
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->permissions = json_encode([
            "master" => $master,
            "admin" => $admin
        ]);
        $user->settings = json_encode([]);
        $user->data = json_encode([]);
        $user->save();

        // Success message, display email and password on seperate lines, the text should be white, but the email and password should be in green
        $this->line('');
        $this->line($userText.' (<fg=yellow>'.$user->name.'</>) created successfully. Here are the login details:');
        $this->line('Email: `<fg=green>'.$user->email.'`</>');
        $this->line('Password: `<fg=green>'.$password.'</>`');

        // A yellow message to remind the user to login and change the email/password
        $this->line('');
        $loginPath = route('wrla.login');
        $this->line('<fg=yellow>Login URL: `</>'.$loginPath.'<fg=yellow>`</>');

        // New line for separation
        $this->line('');

        return 1;
    }
}
