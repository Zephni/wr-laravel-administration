<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Pluralizer;

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
        // Use the stubs/app/WRLA/ManageableModel.stub file to create the app/WRLA/User.php file
        File::ensureDirectoryExists(app_path('WRLA'));

        if (!File::exists(app_path('WRLA/User.php'))) {
            File::copy(__DIR__ . '/stubs/app/WRLA/ManageableModel.stub', app_path('WRLA/User.php'));
        }
    }

    /**
     * Get stub variables
     *
     * @return array
     */
    protected function getStubVariables(): array
    {
        return [
            'NAMESPACE' => 'App\WRLA',
            'MANAGEABLE_MODEL' => 'User',
            'DummyPluralClass' => Pluralizer::plural($this->getClassName()),
        ];
    }
}
