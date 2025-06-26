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
        WRLAHelper::runCommandMiddleware($this);

        // Required user database information
        $userInstance = app(WRLAHelper::getUserModelClass());
        $userTable = str($userInstance->getTable())->afterLast('.')->toString();
        $userConnection = $userInstance->getConnectionName();
        $userColumns = WRLAHelper::getTableColumns($userTable, $userConnection);
        
        while (true) {
            // Choose which column to search user by
            $column = $this->choice('Which user column do you want to search by?', $userColumns, 0);

            // Ask for the value to search
            $value = $this->ask("What is the value of the {$column} column?");

            // Check if user exists
            $user = WRLAHelper::getUserModelClass()::where($column, $value)->first();

            // If user not found by the exact value, do a LIKE search instead and allow to select from a list
            if (!$user) {
                $this->info("No user found with exact value: {$column} = '{$value}' Searching for similar users...");

                while(true) {
                    usleep(1);
                    $users = WRLAHelper::getUserModelClass()::where($column, 'like', '%' . $value . '%')->get();
    
                    if($users->isEmpty()) {
                        $this->error('No users found with similar value. Please try again.');
                        continue;
                    }

                    // Build choice list
                    $userChoiceList = $users->map(fn($user) => "{$user->$column} - <fg=green>ID: {$user->id}</>")->toArray();
    
                    // Note that chosenUserValue will be the user column <fg=green>ID</> and the user ID
                    $chosenUserValue = $this->choice(
                        "Please select a user from the list below by it's index",
                        $userChoiceList
                    );

                    // Extract the id from the chosen value
                    $chosenUserId =  (int) str($chosenUserValue)
                        ->afterLast('ID: ')
                        ->before('/')
                        ->trim()
                        ->toString();

                    // Get the user by the chosen user id
                    $user = $users->firstWhere('id', $chosenUserId);
    
                    // Find the user by the chosen ID
                    if(!$user) {
                        $this->error('Invalid user selection. Please try again.');
                        continue;
                    }
                    
                    break 2;
                }
            }
        }

        // User values (remove anything that isn't within $userColumns)
        $userValues = collect($user->toArray())
            ->only($userColumns)
            ->toArray();

        // Display all fields for this user
        $this->info('User found:');

        // If less or equal to 9 columns, display as table
        if(count($userColumns) <= 9) {
            $this->table(
                $userColumns,
                [$userValues]
            );
        }
        // Otherwise display as a list
        else {
            foreach ($userValues as $key => $value) {
                $this->line("<fg=yellow>{$key}:</> {$value}");
            }
        }

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
