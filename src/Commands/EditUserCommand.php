<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class EditUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wrla:edit-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Edit a user model';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Run commands middleware from config middleware.commands
        WRLAHelper::runCommandMiddleware();

        // Required user database information
        $userInstance = app(WRLAHelper::getUserModelClass());
        $userTable = $userInstance->getTable()->afterLast('.')->toString();
        $userConnection = $userInstance->getConnectionName();
        
        while (true) {
            // Choose which column to search user by
            $columns = WRLAHelper::getTableColumns($userTable, $userConnection);
            $column = $this->choice('Which user column do you want to search by?', $columns, 0);

            // Ask for the value to search
            $value = $this->ask("What is the value of the {$column} column?");

            // Check if user exists
            $user = WRLAHelper::getUserModelClass()::where($column, $value)->first();

            // If user found, break the loop
            if ($user) {
                break;
            }

            // Otherwise, inform the user and ask again
            $this->error('User not found. Please try again.');
        }

        // Display all fields for this user
        $this->info('User found:');
        $this->table(
            array_keys($user->toArray()),
            [$user->toArray()]
        );

        while (true) {
            // Special cases
            $specialCases = [
                'password' => [
                    'message' => 'Enter new password, min 6 characters (will be hashed)',
                    'validation' => 'required|string|min:6',
                    'function' => fn ($value) => Hash::make($value),
                ],
            ];

            // Ask for the field to edit
            $field = $this->ask('Which field do you want to edit?');

            // If special case, ask with custom message
            if (array_key_exists($field, $specialCases)) {
                $message = $specialCases[$field]['message'];
                $validation = $specialCases[$field]['validation'];
                $function = $specialCases[$field]['function'];
                // Default case
            } else {
                $message = "What is the new value for the {$field} field?";
                $validation = 'required|string';
                $function = null;
            }

            // Ask and validate new value for this field
            while (true) {
                $newValue = $this->ask($message);

                // Validate the input
                if ($validation) {
                    $validator = Validator::make([$field => $newValue], [$field => $validation]);
                    if (! $validator->fails()) {
                        break;
                    }
                }

                $this->error($validator->errors()->first($field));
            }

            // If a function is provided, apply it to the new value
            if ($function) {
                $newValue = $function($newValue);
            }

            // Update the user
            $user->$field = $newValue;
            $user->save();
            $this->info('User updated successfully! New user data:');
            $this->table(
                array_keys($user->toArray()),
                [$user->toArray()]
            );

            // Ask if the user wants to edit another field
            $anotherField = $this->confirm('Do you want to edit another field?', true);
            if (! $anotherField) {
                break;
            }
        }

        return 0;
    }
}
