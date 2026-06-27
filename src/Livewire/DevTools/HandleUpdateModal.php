<?php

namespace WebRegulate\LaravelAdministration\Livewire\DevTools;

use LivewireUI\Modal\ModalComponent;
use Throwable;
use WebRegulate\LaravelAdministration\Classes\VersionHandler\BackgroundUpdateProcess;
use WebRegulate\LaravelAdministration\Classes\VersionHandler\VersionHandler;
use WebRegulate\LaravelAdministration\Classes\VersionHandler\WebVersionUpdateContext;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class HandleUpdateModal extends ModalComponent
{
    /**
     * Marker written to the log when a live (background) update finishes.
     */
    private const DONE_MARKER = '[[WRLA_UPDATE_COMPLETE]]';

    /**
     * Console output shown in the modal.
     */
    public string $consoleOutput = '';

    /**
     * How updates are executed: 'live' (background + polling) or 'blocking'.
     */
    public string $mode = 'live';

    /**
     * Whether an update run is currently in progress.
     */
    public bool $running = false;

    /**
     * Whether the current user is permitted to run updates.
     *
     * The modal itself always opens (so we never throw a jarring 404 from a
     * control the UI chose to show); this flag gates the actual update action
     * and what the view offers.
     */
    public bool $authorised = true;

    /**
     * Whether there are pending version updates to apply. Shared with the header
     * indicator so the modal and header can never report different states.
     */
    public bool $updatesAvailable = false;

    /**
     * Whether an update run has completed during this modal session. When true the
     * view offers a button prompting the user to refresh the page behind the modal.
     */
    public bool $updateCompleted = false;

    /**
     * Result of comparing the locally locked composer commit reference against the
     * one Packagist advertises for the installed constraint (eg. dev-main):
     *   true  = a `composer update` would pull a newer commit,
     *   false = local is in sync with Packagist,
     *   null  = the check could not be completed (offline / package missing).
     */
    public ?bool $composerUpdateAvailable = null;

    public function mount()
    {
        // Resolve authorisation, but NEVER abort(404) here. The update indicator /
        // button is rendered by the package, so clicking it must always resolve to a
        // usable modal rather than a 404 — even if the dev gate resolves differently
        // in this (Livewire) sub-request than it did on the full page render.
        $this->authorised = WRLAHelper::showVersionUpdateBar();

        $this->mode = config('wr-laravel-administration.developer.update.mode', 'live') === 'blocking'
            ? 'blocking'
            : 'live';

        if (!$this->authorised) {
            $this->consoleOutput = 'Update tools are not available for your account.' . PHP_EOL;
            $this->dispatch('dev-tools.handle-update-modal.opened');
            return;
        }

        // Determine pending state from the same source of truth as the header
        $versionHandler = new VersionHandler(new WebVersionUpdateContext());
        $this->updatesAvailable = $versionHandler->hasPendingUpdates();

        // Remote check: does the commit reference locked in composer.lock differ
        // from the one Packagist advertises for our constraint (eg. dev-main)?
        $this->composerUpdateAvailable = VersionHandler::isComposerUpdateAvailable();

        // Show the current applied version and pending status on open
        $currentVersion = $versionHandler->getVersion() ?? '0.1.0';
        $this->consoleOutput = 'Current version: ' . $currentVersion . PHP_EOL;

        $hasMajor = $this->updatesAvailable;
        $hasMinor = $this->composerUpdateAvailable === true;

        if ($hasMajor && $hasMinor) {
            $this->consoleOutput .= 'Major and minor updates are available — see the options below to apply them.' . PHP_EOL;
        } elseif ($hasMajor) {
            $this->consoleOutput .= 'A major update is available — press "Run major / breaking updates" to apply pending changes.' . PHP_EOL;
        } elseif ($hasMinor) {
            $this->consoleOutput .= 'A minor update is available — run composer update to apply it.' . PHP_EOL;
        } else {
            $this->consoleOutput .= 'You are on the latest version, no updates required.' . PHP_EOL;
        }

        // Dispatch an event indicating that the modal has been opened
        $this->dispatch('dev-tools.handle-update-modal.opened');
    }

    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.dev-tools.handle-update-modal'));
    }

    /**
     * Trigger an update run using the configured mode.
     */
    public function runCommand()
    {
        // Authorisation gate for the actual (privileged) update action. We refuse
        // gracefully rather than abort(404) so the user gets clear feedback instead
        // of a broken-looking response.
        if (!WRLAHelper::showVersionUpdateBar()) {
            $this->authorised = false;
            $this->consoleOutput = 'You do not have permission to run updates.' . PHP_EOL;
            return;
        }

        $this->updateCompleted = false;

        $this->mode === 'blocking'
            ? $this->runBlocking()
            : $this->runLive();
    }

    /**
     * Run composer update only — no version migrations.
     * Always runs in blocking mode since there is no dedicated artisan command for it.
     */
    public function runComposerOnly(): void
    {
        if (!WRLAHelper::showVersionUpdateBar()) {
            $this->authorised = false;
            $this->consoleOutput = 'You do not have permission to run updates.' . PHP_EOL;
            return;
        }

        @set_time_limit(0);
        $this->running = true;
        $this->updateCompleted = false;
        $context = new WebVersionUpdateContext();

        try {
            $versionHandler = new VersionHandler($context);
            $success = $versionHandler->runComposerUpdate();
            if ($success) {
                $versionHandler->runOptimizeClear();
                $context->info('Composer update completed successfully.');
            }
        } catch (Throwable $e) {
            $context->error($e->getMessage());
        }

        $this->consoleOutput = $this->stripAnsi($context->getOutput());
        $this->running = false;
        $this->updateCompleted = true;

        // Re-check so the in-sync / update-available hint reflects the new state.
        $this->composerUpdateAvailable = VersionHandler::isComposerUpdateAvailable();
    }

    /**
     * Blocking mode: run all pending updates synchronously and show the output once finished.
     */
    protected function runBlocking(): void
    {
        @set_time_limit(0);

        $this->running = true;

        $context = new WebVersionUpdateContext();

        try {
            (new VersionHandler($context))->runUpdates();
        } catch (\Throwable $e) {
            $context->error($e->getMessage());
        }

        // Preserve any message already shown (e.g. a live-mode fallback notice)
        $this->consoleOutput .= $this->stripAnsi($context->getOutput());
        $this->running = false;
        $this->updateCompleted = true;

        // Refresh pending state so the modal/header reflect the new applied version
        $this->updatesAvailable = (new VersionHandler(new WebVersionUpdateContext()))->hasPendingUpdates();
    }

    /**
     * Live mode: spawn `wrla:update` as a detached background process that streams its
     * output to a log file. The modal then polls that file via pollOutput().
     */
    protected function runLive(): void
    {
        try {
            (new BackgroundUpdateProcess())->start(
                $this->logPath(),
                self::DONE_MARKER,
                'wrla:update --no-interaction'
            );

            $this->running = true;
            $this->consoleOutput = 'Starting update...' . PHP_EOL;
        } catch (\Throwable $e) {
            // Could not launch a background process on this host: fall back to blocking mode.
            $this->consoleOutput = 'Could not start background update (' . $e->getMessage() . ')' . PHP_EOL
                . 'Falling back to blocking mode...' . PHP_EOL;
            $this->runBlocking();
        }
    }

    /**
     * Polled by the modal while a live update is running to stream the latest output.
     */
    public function pollOutput(): void
    {
        if (!$this->running) {
            return;
        }

        $logPath = $this->logPath();
        if (!file_exists($logPath)) {
            return;
        }

        $output = file_get_contents($logPath);

        // Detect completion via the marker, then strip it from the displayed output
        if (str_contains($output, self::DONE_MARKER)) {
            $output = trim(str_replace(self::DONE_MARKER, '', $output));
            $this->running = false;
            $this->updateCompleted = true;

            // Refresh pending state now the background update has finished
            $this->updatesAvailable = (new VersionHandler(new WebVersionUpdateContext()))->hasPendingUpdates();
        }

        $this->consoleOutput = $this->stripAnsi($output);
    }

    /**
     * Remove ANSI escape sequences (colour codes, cursor controls, etc.) from
     * command output so it renders as plain text rather than raw control characters.
     */
    protected function stripAnsi(string $output): string
    {
        // Matches CSI sequences (e.g. \e[32;1m) and other escape sequences.
        return (string) preg_replace('/\x1b\[[0-9;?]*[ -\/]*[@-~]|\x1b[@-_]/', '', $output);
    }

    /**
     * Absolute path to the log file used for live updates.
     */
    protected function logPath(): string
    {
        return storage_path('app/wrla/update.log');
    }
}
