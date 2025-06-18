<?php

namespace WebRegulate\LaravelAdministration\Classes\VersionHandler;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class VersionHandler
{
    public static $localPackageCurrentVersion = null;
    public static $localPackageCurrentSha = null;
    public static $remotePackageLatestSha = null;

    private string $versionFilePath = 'vendor/wr-laravel-administration/version.json';
    private Command $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    private function getVersionFilePath(): string
    {
        return public_path($this->versionFilePath);
    }

    private function updateVersionData(string $version)
    {
        $versionData = [
            'version' => $version,
            'updated' => now()->toDateTimeString()
        ];

        file_put_contents($this->getVersionFilePath(), json_encode($versionData, JSON_PRETTY_PRINT));
    }

    private function getVersionActionMappings()
    {
        return [
            /**
             * TODO: Handle new vendor css / js setup, install ck editor.
             */
            // '0.1.0' => function(Command $command) {
                
            // }
        ];
    }

    public function getVersionData(): ?array
    {
        if (!file_exists($this->getVersionFilePath())) {
            return null;
        }

        $versionData = file_get_contents($this->getVersionFilePath());
        return json_decode($versionData, true);
    }

    public function getVersion(): ?string
    {
        return $this->getVersionData()['version'] ?? null;
    }

    public function runUpdates(): bool
    {
        $this->command->line('');

        // Initialised found update var
        $foundUpdate = false;

        // Get version data
        $versionData = $this->getVersionData();

        // If does not exist
        if (!$versionData) {
            // This is hardcoded because it's essentially the "first version" we start handling updates from.
            $this->updateVersionData('0.1.0');
        }

        // Extract current version or use null
        $currentVersion = $versionData['version'] ?? null;

        // Retrieve version-action mappings
        $actionMappings = $this->getVersionActionMappings();

        // Loop through each version and its corresponding update action
        foreach ($actionMappings as $version => $action) {
            // Check if no version is set or current version is older
            if ($currentVersion == null || version_compare($currentVersion, $version, '<')) {
                // Set found update to true
                $foundUpdate = true;

                // Inform we are about to attempt the update
                $this->command->line("Applying version changes for: $version");
                $this->command->line("-------------------------------------");

                // Execute the associated action
                try {
                    call_user_func($action, $this->command);
                    $this->command->info("Successfully applied changes for version: $version");
                } catch (\Exception $e) {
                    // If an error occurs, inform the user and stop the process
                    $this->command->error("Error while applying version changes for $version: " . $e->getMessage());
                    return false;
                }

                // Update the stored version data
                $this->updateVersionData($version);

                // Line and mini sleep
                $this->command->line('');
                usleep(100);
            }
        }

        // If no updates were found, inform the user
        if (!$foundUpdate) {
            $this->command->info('No updates required, current version: ' . ($currentVersion ?? '0.1.0').PHP_EOL);
            return false;
        }

        // Indicate that all updates have successfully run
        return true;
    }

    /**
     * Sets properties for local and remote package information.
     */
    public static function buildLocalAndRemotePackageInformation(): void
    {
        /* Local
        ----------------------------------------------------------------*/
        // Get actual version from composer.lock instead
        $composerLockPath = base_path('composer.lock');

        // Find applicable package data in composer.lock
        $composerData = json_decode(file_get_contents($composerLockPath), true);
        if (isset($composerData['packages'])) {
            foreach ($composerData['packages'] as $package) {
                if ($package['name'] === 'webregulate/laravel-administration') {
                    VersionHandler::$localPackageCurrentVersion = $package['version'] ?? null;
                    VersionHandler::$localPackageCurrentSha = $package['dist']['reference'] ?? null;
                }
            }
        }

        /* Remote
        ----------------------------------------------------------------*/
        try {
            $appKey = config('app.key', config('app.url', 'invalid-key'));
            VersionHandler::$remotePackageLatestSha = cache()->remember("wrla.$appKey.remotePackageLatestSha", 3600, function ()  {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => 'User-Agent: WebRegulate Laravel Administration - '.config('app.url', 'Unknown domain')
                    ]
                ]);
                
                $branchData = json_decode(
                    file_get_contents('https://api.github.com/repos/Zephni/wr-laravel-administration/branches/main', false, $context)
                , true);
        
                return $branchData['commit']['sha'] ?? null;
            });
        }
        // Just in case we cannot retrieve the remote SHA, we set it to the local one so no update 
        catch (\Exception $e) {
            VersionHandler::$remotePackageLatestSha = VersionHandler::$localPackageCurrentSha;
        }
    }
}