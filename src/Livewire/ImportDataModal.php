<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use LivewireUI\Modal\ModalComponent;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class ImportDataModal extends ModalComponent
{
    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.import-data-modal'), [

        ]);
    }
}