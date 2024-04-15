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
        // Get the model name and file path
        $model = $this->argument('model');
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
        $icon = $this->ask('Icon for the model', 'fa fa-question-circle');

        // Now we use WRLAHelper to generate the file from the stub
        WRLAHelper::generateFileFromStub(
            'ManageableModel.stub',
            self::getStubVariables($model, $icon),
            app_path('WRLA/' . $filePath . '.php'),
            $forceOverwrite
        );

        // Success message
        $this->info("Manageable model $model created successfully here: " . WRLAHelper::removeBasePath(app_path('WRLA/' . $filePath . '.php')));

        // New line for separation
        $this->line('');
    }

    /**
     * Get stub variables
     *
     * @return array
     */
    public static function getStubVariables(string $model, string $icon): array
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
