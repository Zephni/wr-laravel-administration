<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use LivewireUI\Modal\ModalComponent;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class ImportDataModal extends ModalComponent
{
    public function mount()
    {
        // Emit a Livewire event when the modal is fully opened
        $this->dispatch('importDataModalOpened');
    }

    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.import-data-modal'), [

        ]);
    }
}