<?php

namespace WebRegulate\LaravelAdministration\Livewire\DevTools;

use LivewireUI\Modal\ModalComponent;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class DevToolsModal extends ModalComponent
{
    public function mount()
    {
        // 404 if dev tools are not enabled for this user
        if (!WRLAHelper::userIsDev()) {
            abort(404);
        }

        // Dispatch an event indicating that the modal has been opened
        $this->dispatch('dev-tools.dev-tools-modal.opened');
    }

    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.dev-tools.dev-tools-modal'));
    }
}