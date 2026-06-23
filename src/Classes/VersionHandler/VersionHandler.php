<?php

namespace WebRegulate\LaravelAdministration\Classes\VersionHandler;

class VersionHandler
{
    public static $localPackageCurrentVersion = null;
    public static $localPackageCurrentSha = null;
    public static $remotePackageLatestSha = null;

    private string $versionFilePath = 'vendor/wr-laravel-administration/version.json';
    private VersionUpdateContext $context;

    public function __construct(VersionUpdateContext $context)
    {
        $this->context = $context;
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

    /**
     * Discover all version update definitions in the Versions directory,
     * instantiated and sorted in ascending version order.
     *
     * @return array<int, VersionUpdate>
     */
    private function getVersionUpdates(): array
    {
        $versionsDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'Versions';

        if (!is_dir($versionsDirectory)) {
            return [];
        }

        $updates = [];

        foreach (glob($versionsDirectory . DIRECTORY_SEPARATOR . '*.php') as $file) {
            $class = __NAMESPACE__ . '\\Versions\\' . pathinfo($file, PATHINFO_FILENAME);

            if (!class_exists($class)) {
                continue;
            }

            $instance = new $class();

            if (!$instance instanceof VersionUpdate) {
                continue;
            }

            $updates[] = $instance;
        }

        // Sort ascending by version so updates apply in order
        usort($updates, fn(VersionUpdate $a, VersionUpdate $b) => version_compare($a->version(), $b->version()));

        return $updates;
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
        $this->context->line('');

        // Initialised found update var
        $foundUpdate = false;

        // Get version data
        $versionData = $this->getVersionData();

        // If does not exist
        if (!$versionData) {
            // This is hardcoded because it's essentially the "first version" we start handling updates from.
            $this->updateVersionData('0.1.0');
        }

        // Extract current version or use the starting version
        $currentVersion = $versionData['version'] ?? '0.1.0';

        // Discover all version update definitions (ascending order)
        $versionUpdates = $this->getVersionUpdates();

        // Loop through each version update and run the ones newer than the current version
        foreach ($versionUpdates as $versionUpdate) {
            $version = $versionUpdate->version();

            // Skip versions we've already applied
            if (version_compare($currentVersion, $version, '>=')) {
                continue;
            }

            // Set found update to true
            $foundUpdate = true;

            // Inform we are about to attempt the update
            $this->context->line("Applying version changes for: $version - {$versionUpdate->title()}");
            $this->context->line("-------------------------------------");

            // Start each version with a clean change log so its summary only
            // reflects the steps belonging to this version.
            $this->context->clearChangeLog();

            // Execute the version update
            try {
                $versionUpdate->run($this->context);

                // Summarise what this version actually changed (if it reported any outcomes)
                $summary = $this->context->changeSummaryLine();
                if ($summary !== null) {
                    $this->context->line("Summary of changes - {$summary}");
                }

                $this->context->info("Successfully applied changes for version: $version");
            } catch (\Throwable $e) {
                // If an error occurs, inform the user and stop the process
                $this->context->error("Error while applying version changes for $version: " . $e->getMessage());
                return false;
            }

            // Update the stored version data
            $this->updateVersionData($version);
            $currentVersion = $version;

            // Line and mini sleep
            $this->context->line('');
            usleep(100);
        }

        // If no updates were found, inform the user
        if (!$foundUpdate) {
            $this->context->info('No updates required, current version: ' . $currentVersion . PHP_EOL);
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