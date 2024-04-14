<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Pluralizer;

class CreateManageableModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wrla:manageable-model {model}';

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
        // Use the stubs/app/WRLA/ManageableModel.stub file to create the app/WRLA/NewModel.php file
        File::ensureDirectoryExists(app_path('WRLA'));

        // Get the model name
        $model = $this->argument('model');

        // Get the model stub
        $stub = File::get(__DIR__ . '/../stubs/app/WRLA/ManageableModel.stub');

        // Question 1: Icon for the model (default: fa fa-question-circle)
        $icon = $this->ask('Icon for the model (default: fa fa-question-circle)', 'fa fa-question-circle');

        // Replace the stub variables
        foreach ($this->getStubVariables($model, $icon) as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        // Convert model to file path and create the file
        $filePath = str($model)->replace('\\', '/')->__toString();
        File::put(app_path('WRLA/' . $filePath . '.php'), $stub);

        // Success message
        $this->info("Manageable model $model created successfully here: app/WRLA/$filePath.php");
    }

    /**
     * Get stub variables
     *
     * @return array
     */
    protected function getStubVariables(string $model, string $icon): array
    {
        // If the model contains a backslash, it means it's namespaced
        if (str($model)->contains('\\')) {
            $namespace = 'App\\WRLA\\' . str($model)->beforeLast('\\')->__toString();
            $model = str($model)->afterLast('\\')->__toString();
        // Otherwise, it's just the model
        } else {
            $namespace = 'App\\WRLA';
        }

        return [
            '{{ $NAMESPACE }}' => $namespace,
            '{{ $MODEL }}' => $model,
            '{{ $URL_ALIAS }}' => str($model)->kebab()->lower()->__toString(),
            '{{ $DISPLAY_NAME }}' => str($model)->headline()->__toString(),
            '{{ $ICON }}' => $icon,
        ];
    }
}
