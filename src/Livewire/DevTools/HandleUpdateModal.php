<?php

namespace WebRegulate\LaravelAdministration\Livewire\DevTools;

use LivewireUI\Modal\ModalComponent;
use Illuminate\Support\Facades\Process;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use Illuminate\Process\Pipes\StreamPipe;

class HandleUpdateModal extends ModalComponent
{
    /**
     * Console output
     */
    public string $consoleOutput = '';

    public string $command = '';

    public function mount()
    {
        // 404 if dev tools are not enabled for this user
        if (!WRLAHelper::userIsDev()) {
            abort(404);
        }

        // Dispatch an event indicating that the modal has been opened
        $this->dispatch('dev-tools.handle-update-modal.opened');
    }

    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.dev-tools.handle-update-modal'));
    }

    public function runCommand()
    {
        $command = $this->command;
        $this->command = '';

        // 1. Create a temporary "stream" in memory
        $inputStream = fopen('php://memory', 'r+');

        // 2. Write a newline character to the stream (this is "Enter")
        fwrite($inputStream, "\n");

        // 3. Rewind the stream so the process can read it from the start
        rewind($inputStream);

        // 4. Use run() and feed the stream resource directly to input()
        $result = Process::path(base_path())
            ->env($_SERVER)
            ->input($inputStream) // This is the corrected line
            ->run($command);

        // The command will now run to completion
        $this->consoleOutput = $this->consoleOutput.'
'.$result->output();

        // 5. Close the stream resource
        fclose($inputStream);
    }
}