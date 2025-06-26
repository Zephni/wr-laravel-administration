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

        while (true) {
            // Display user fields and values
            $this->displayKeysAndValues($userValues);

            // Get password field from user data model
            try {
                $passwordField = WRLAHelper::getUserDataModelClass()::getPasswordField();
            } catch (\Exception $e) {
                $this->error('Error getting wrlaUserData password field: ' . $e->getMessage());
                dd('Aborting due to error.');
            }

            // Special cases
            $specialCases = [
                $passwordField => [
                    'message' => 'Enter new password, min 6 characters (will be hashed)',
                    'validation' => 'required|string|min:6',
                    'function' => function ($value) {
                        try {
                            return WRLAHelper::getUserDataModelClass()::hashPassword($value); 
                        } catch (\Exception $e) {
                            $this->error('Error hashing password: ' . $e->getMessage());
                            dd('Aborting due to hashing error.');
                        }
                    },
                ],
            ];

            // Special one off options
            $this->line(PHP_EOL . 'Special commands:');
            $specialUserOptions = ['wrla:user-data', 'exit'];
            foreach ($specialUserOptions as $option) {
                $this->line("<fg=yellow>{$option}</>: Edit user data relationship");
            }

            // Ask for the field to edit
            $field = $this->ask("Which field do you want to edit, or type a special command from above");

            // If user wants to exit
            if (strtolower($field) === 'exit') {
                $this->info('Exiting user edit command.');
                return 0;
            }

            // If wrla_user_data is entered, handle relationship editing
            if ($field === 'wrla:user-data') {
                // Get wrla_user_data database information
                $wrlaUserDataInstance = app(WRLAHelper::getUserDataModelClass());
                $wrlaUserDataTable = str($wrlaUserDataInstance->getTable())->afterLast('.')->toString();
                $wrlaUserDataConnection = $wrlaUserDataInstance->getConnectionName();
                $wrlaUserDataColumns = WRLAHelper::getTableColumns($wrlaUserDataTable, $wrlaUserDataConnection);

                while(true) {
                    // Display wrla_user_data fields as table
                    $this->info('Available fields for wrla_user_data:');
                    $this->displayKeysAndValues($user->wrlaUserData->toArray());
    
                    // Ask for the field to edit
                    $specialWrlaUserDataCases = [
                        'make:admin' => [
                            'message' => 'Give admin permission',
                            'function' =>  fn($user) => $user->wrlaUserData->setPermission('admin', true)
                        ],
                        'make:master' => [
                            'message' => 'Give master permission',
                            'function' =>  fn($user) => $user->wrlaUserData->setPermission('master', true) && $user->wrlaUserData->setPermission('admin', true)
                        ],
                        'revoke:admin' => [
                            'message' => 'Revoke admin permission',
                            'function' =>  fn($user) => $user->wrlaUserData->setPermission('admin', false)
                        ],
                        'revoke:master' => [
                            'message' => 'Revoke master and admin permissions',
                            'function' =>  fn($user) => $user->wrlaUserData->setPermission('master', false) && $user->wrlaUserData->setPermission('admin', false)
                        ],
                        'revoke:all' => [
                            'message' => 'Revoke all permissions',
                            'function' =>  function($user) {
                                $wrlaUserData = $user->wrlaUserData;
                                $wrlaUserData->permissions = '[]';
                                $wrlaUserData->save();
                            }
                        ],
                        'back' => [
                            'message' => 'Go back to user edit',
                            'function' => fn($user) => 'back'
                        ],
                    ];

                    // Show special commands
                    $this->info(PHP_EOL.'Special commands:');
                    foreach ($specialWrlaUserDataCases as $key => $specialCase) {
                        $this->line("<fg=yellow>{$key}:</> {$specialCase['message']}");
                    }

                    $this->line('');
                    $field = $this->ask('Which user data field do you want to edit, or use special commands above');

                    // Check if the field is a special case
                    if (array_key_exists($field, $specialWrlaUserDataCases)) {
                        $return = $specialWrlaUserDataCases[$field]['function']($user);
                        $this->line('');

                        if($return === 'back') {
                            $this->info('Going back to user edit.');
                            continue 2; // Go back to the main user edit loop
                        }

                        continue;
                    }
                    
                    // Check if the field exists in wrla_user_data
                    if (!in_array($field, $wrlaUserDataColumns)) {
                        $this->error("Field {$field} does not exist in {$wrlaUserDataTable}. Please try again.");
                        continue;
                    }

                    $this->info("Field {$wrlaUserDataTable}.{$field} updated successfully!");
                    break;
                }
            }

            // If special case, ask with custom message
            if (array_key_exists($field, $specialCases)) {
                $message = $specialCases[$field]['message'];
                $validation = $specialCases[$field]['validation'];
                $function = $specialCases[$field]['function'];
            }
            // If field does not exist in user model, show error
            elseif (!in_array($field, $userColumns)) {
                $this->error("Field {$field} does not exist in {$userTable}. Please try again.");
                continue;
            }
            // Default case
            else {
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
            $this->displayKeysAndValues($user->only($userColumns));

            // Ask if the user wants to edit another field
            $anotherField = $this->confirm('Do you want to edit another field?', true);
            if (! $anotherField) {
                break;
            }
        }

        return 0;
    }

    private function displayKeysAndValues(array $keyValues)
    {
        // If less or equal to 9 columns, display as table
        if(count($keyValues) <= 7) {
            $this->table(
                array_keys($keyValues),
                [$keyValues],
                'compact'
            );
        }
        // Otherwise display as a list
        else {
            foreach ($keyValues as $key => $value) {
                $this->line("<fg=yellow>{$key}:</> {$value}");
            }
        }
    }
}
