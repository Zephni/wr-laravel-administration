<?php

namespace WebRegulate\LaravelAdministration\Livewire\DevTools;

use LivewireUI\Modal\ModalComponent;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class HandleUpdateModal extends ModalComponent
{
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
}