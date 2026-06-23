<?php

namespace WebRegulate\LaravelAdministration\Classes\VersionHandler;

use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class VersionHandler
{
    public static $localPackageCurrentVersion = null;
    public static $remoteLatestVersion = null;

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

    /**
     * Get all version update definitions that have not yet been applied
     * (i.e. whose version is greater than the currently stored version).
     *
     * @return array<int, VersionUpdate>
     */
    public function getPendingUpdates(): array
    {
        $currentVersion = $this->getVersion() ?? '0.1.0';

        return array_values(array_filter(
            $this->getVersionUpdates(),
            fn(VersionUpdate $update) => version_compare($currentVersion, $update->version(), '<')
        ));
    }

    /**
     * Whether there are any pending version updates to apply.
     *
     * This is the single source of truth shared by the header indicator and the
     * update modal, so the two can never disagree (e.g. the header claiming an
     * update is available while the modal reports it is already up to date).
     */
    public function hasPendingUpdates(): bool
    {
        return count($this->getPendingUpdates()) > 0;
    }

    /**
     * Convenience accessor for views: determine whether updates are pending
     * without needing to construct a context manually.
     */
    public static function pendingUpdatesAvailable(): bool
    {
        return (new self(new WebVersionUpdateContext()))->hasPendingUpdates();
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

        // Always pull the latest package code via composer first, regardless of the
        // version recorded in version.json. This lets the user update the package
        // through the UI or artisan command even when no per-version migrations are
        // pending, ensuring they can fetch newer code before any version handlers run.
        $this->context->line('Updating composer dependencies...');
        $this->context->line('-------------------------------------');

        if (!$this->runComposerUpdate()) {
            $this->context->error('composer update did not complete successfully.');
            return false;
        }

        $this->context->line('');

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

                // Record a single wrla event log entry describing every part of
                // this version's update, with the changes as a numerically
                // indexed array.
                WRLAHelper::logEvent(
                    "WRLA updated to version {$version} - {$versionUpdate->title()}",
                    [
                        'version' => $version,
                        'title' => $versionUpdate->title(),
                        'summary' => $summary,
                        'changes' => $this->context->recordedChangeLines(),
                    ]
                );

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
            $this->context->info('Composer dependencies updated. No further version changes required, current version: ' . $currentVersion . PHP_EOL);
            return false;
        }

        // Indicate that all updates have successfully run
        return true;
    }

    /**
     * Run `composer update` honouring the developer.composer.no_dev config (the
     * list of app environments that should update without dev dependencies).
     *
     * This is run unconditionally at the start of an update so the package code
     * can always be refreshed via the UI or artisan command, independent of the
     * per-version migrations tracked in version.json.
     */
    private function runComposerUpdate(): bool
    {
        $noDevEnvironments = config('wr-laravel-administration.developer.composer.no_dev', ['production']);
        $useNoDev = in_array(app()->environment(), (array) $noDevEnvironments, true);

        $command = ['composer', 'update', '--no-interaction'];
        if ($useNoDev) {
            $command[] = '--no-dev';
        }

        $this->context->line('Running: ' . implode(' ', $command));

        $success = $this->context->runProcess($command);

        if (!$success) {
            $this->context->error('composer update failed.');
        }

        return $success;
    }

    /**
     * Resolve the locally installed package version from composer.lock and fetch
     * the latest available version from the GitHub Pages hosted version.json.
     *
     * Whether local migrations are pending is determined separately by the version
     * update system (see {@see hasPendingUpdates()}).
     */
    public static function buildLocalAndRemotePackageInformation(): void
    {
        // Get actual version from composer.lock
        $composerLockPath = base_path('composer.lock');

        if (file_exists($composerLockPath)) {
            $composerData = json_decode(file_get_contents($composerLockPath), true);
            if (isset($composerData['packages'])) {
                foreach ($composerData['packages'] as $package) {
                    if ($package['name'] === 'webregulate/laravel-administration') {
                        VersionHandler::$localPackageCurrentVersion = $package['version'] ?? null;
                    }
                }
            }
        }

        // Fetch the latest published version from GitHub Pages (fail silently).
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
            $json = @file_get_contents(
                'https://zephni.github.io/wr-laravel-administration/version.json',
                false,
                $ctx
            );
            if ($json !== false) {
                $data = json_decode($json, true);
                VersionHandler::$remoteLatestVersion = $data['version'] ?? null;
            }
        } catch (\Throwable) {
            // Remote unavailable — header falls back to local pending-update check.
        }
    }

    /**
     * Whether the remote GitHub Pages version.json advertises a version that is
     * newer than the last version applied locally.
     *
     * Returns false when the remote version could not be fetched so the UI
     * degrades gracefully to the local pending-update check.
     */
    public static function isRemoteUpdateAvailable(): bool
    {
        if (self::$remoteLatestVersion === null) {
            return false;
        }

        $localApplied = (new self(new WebVersionUpdateContext()))->getVersion() ?? '0.1.0';

        return version_compare($localApplied, self::$remoteLatestVersion, '<');
    }
}