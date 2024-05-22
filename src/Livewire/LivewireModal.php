<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use Livewire\Component;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class LivewireModal extends Component
{
    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.livewire-modal'));
    }
}
