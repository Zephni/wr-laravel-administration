<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use LivewireUI\Modal\ModalComponent;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class ImportDataModal extends ModalComponent
{
    public $manageableModelClass;

    public function mount(string $manageableModelClass)
    {
        // Emit a Livewire event when the modal has opened
        $this->dispatch('importDataModalOpened');

        // Set manageable model
        $this->manageableModelClass = $manageableModelClass;
    }

    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.import-data-modal'), [

        ]);
    }
}