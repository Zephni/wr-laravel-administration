<?php
namespace WebRegulate\LaravelAdministration\Classes\ConfiguredModeBasedHandlers;

use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ConfiguredModeBasedHandler;

class LogsHandler extends ConfiguredModeBasedHandler
{
    /**
     * Base configuration path
     */
    public function baseConfigurationPath(): string {
        return 'logs';
    }

    /**
     * Get view based on current configuration mode.
     */
    public function getView() {
        return match ($this->mode) {
            'wrla' => $this->getWrlaView(),
            'opcodesio/log-viewer' => $this->getOpCodesIoLogViewerView(),
            default => "Logs not configured or unsupported mode: {$this->mode}",
        };
    }

    /* WRLA
    ----------------------------------------------------------------------------*/
    /**
     * Get WRLA logs view.
     */
    protected function getWrlaView() {
        return view(WRLAHelper::getViewPath('livewire-content'), [
            'title' => 'View Logs',
            'livewireComponentAlias' => 'wrla.logs',
            'livewireComponentData' => [],
        ]);
    }

    /* OPCODES.IO LOG VIEWER
    ----------------------------------------------------------------------------*/
    /**
     * Get opcodesio log viewer view.
     */
    protected function getOpCodesIoLogViewerView() {
        // If display_within_wrla is true, we will display the log viewer within WRLA
        if ($this->currentConfiguration['display_within_wrla'] == true)
        {
            $logViewerPath = config('log-viewer.route_path', 'log-viewer');

            return view(WRLAHelper::getViewPath('standard-content'), [
                'title' => 'View Logs',
                'content' => <<<BLADE
                    <iframe
                        wire:ignore
                        src="/{$logViewerPath}"
                        class="relative border-0"
                        style="
                            left: -50px;
                            top: -30px;
                            width: calc(100% + 85px);
                            height: calc(100% - 0px);
                        "></iframe>
                BLADE
            ]);
        }
        // Otherwise redirect to the log viewer
        else
        {
            return redirect()->to('/log-viewer');
        }
    }
}