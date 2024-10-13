<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Facades\File;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class CreateManageableModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wrla:manageable-model {model?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a manageable model';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // Check if model argument is set
        if (!$this->argument('model'))
        {
            // Ask user to present model name
            $model = $this->ask('Please provide a model class using studly case (eg. ModelName)');
        }
        else
        {
            // Get the model name from the argument
            $model = $this->argument('model');
        }

        // Get the file path for the model
        $filePath = str($model)->replace('\\', '/')->__toString();

        // Check if file already exists, if so ask the user if they want to overwrite it
        $forceOverwrite = false;
        if (File::exists(app_path('WRLA/' . $filePath . '.php'))) {
            if ($this->confirm('The model already exists. Do you want to overwrite it?', false)) {
                $forceOverwrite = true;
            } else {
                $this->warn('Model creation cancelled.');
                return 0;
            }
        }

        // Question 1: Icon for the model (default: fa fa-question-circle)
        $icon = $this->ask('Icon for the model (https://fontawesome.com/v5/search)', 'fa fa-question-circle');

        // Now we use WRLAHelper to generate the file from the stub
        WRLAHelper::generateFileFromStub(
            'ManageableModel.stub',
            static::getStubVariables($model, $icon),
            app_path('WRLA/' . $filePath . '.php'),
            $forceOverwrite
        );

        // Success message
        $this->info("Manageable model $model created successfully here: " . WRLAHelper::removeBasePath(app_path('WRLA/' . $filePath . '.php')));

        // New line for separation
        $this->line('');

        // Check whether model exists
        $baseModelExists = File::exists(app_path('Models/' . $filePath . '.php'));

        // Question 2: Ask if user wants to create the model
        $createModel = $this->confirm(!$baseModelExists
            ? 'Create the '.$model.' model?'
            : 'The base model already exists. Override '.$model.' model?'
        , !$baseModelExists);

        // If create model, use the make:model command to create the model
        if ($createModel) {
            $this->call('make:model', ['name' => $model]);
        }

        // New line for separation
        $this->line('');

        // Check whether file containing create_snaked_plural_table.php exists in the migrations folder
        $migrationExists = false;
        foreach (File::files(database_path('migrations')) as $file) {
            if (str($file->getFilename())->contains('create_'.str($filePath)->plural()->snake()->lower()->__toString().'_table')) {
                $migrationExists = true;
                break;
            }
        }

        // Question 3: Ask if user wants to create the migration, either no, or the migration name
        $createMigration = $this->confirm(!$migrationExists
            ? 'Create the create_'.str($model)->plural()->snake()->lower()->__toString().'_table migration?'
            : 'The migration already exists. Override create_'.str($model)->plural()->snake()->lower()->__toString().'_table migration?'
        , !$migrationExists);

        // If create migration, use the make:migration command to create the migration
        if ($createMigration) {
            $this->call('make:migration', ['name' => 'Create'.str($model)->plural()->__toString().'Table']);
        }

        // New line for separation
        $this->line('');

        return 1;
    }

    /**
     * Get stub variables
     *
     * @return array
     */
    public static function getStubVariables(string $model, string $icon, array $overrides = []): array
    {
        $modelWithPath = $model;

        // If the model contains a backslash, it means it's namespaced
        if (str($model)->contains('\\')) {
            $namespace = 'App\\WRLA\\' . str($model)->beforeLast('\\')->__toString();
            $model = str($modelWithPath)->afterLast('\\')->__toString();
        // Otherwise, it's just the model
        } else {
            $namespace = 'App\\WRLA';
        }

        return [
            '{{ $NAMESPACE }}' => $namespace,
            '{{ $MODEL }}' => $model,
            '{{ $MODEL_WITH_PATH }}' => $modelWithPath,
            '{{ $URL_ALIAS }}' => str($model)->kebab()->lower()->__toString(),
            '{{ $DISPLAY_NAME }}' => str($model)->headline()->__toString(),
            '{{ $ICON }}' => $icon,
        ];
    }
}
