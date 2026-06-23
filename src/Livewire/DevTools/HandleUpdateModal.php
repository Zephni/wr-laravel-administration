<?php

namespace WebRegulate\LaravelAdministration\Livewire\DevTools;

use LivewireUI\Modal\ModalComponent;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\VersionHandler\VersionHandler;
use WebRegulate\LaravelAdministration\Classes\VersionHandler\WebVersionUpdateContext;
use WebRegulate\LaravelAdministration\Classes\VersionHandler\BackgroundUpdateProcess;

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

    public function mount()
    {
        // 404 if dev tools are not enabled for this user
        if (!WRLAHelper::userIsDev()) {
            abort(404);
        }

        $this->mode = config('wr-laravel-administration.developer.update.mode', 'live') === 'blocking'
            ? 'blocking'
            : 'live';

        // Show the current applied version on open
        $this->consoleOutput = 'Current version: ' . ($this->currentVersion() ?? '0.1.0') . PHP_EOL;

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
        if (!WRLAHelper::userIsDev()) {
            abort(404);
        }

        $this->mode === 'blocking'
            ? $this->runBlocking()
            : $this->runLive();
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
        $this->consoleOutput .= $context->getOutput();
        $this->running = false;
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
        }

        $this->consoleOutput = $output;
    }

    /**
     * Absolute path to the log file used for live updates.
     */
    protected function logPath(): string
    {
        return storage_path('app/wrla/update.log');
    }

    /**
     * The currently applied WRLA version.
     */
    protected function currentVersion(): ?string
    {
        return (new VersionHandler(new WebVersionUpdateContext()))->getVersion();
    }
}
