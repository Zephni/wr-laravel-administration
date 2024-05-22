<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use Livewire\Component;
use Illuminate\Contracts\View\View;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class LivewireModal extends Component
{
    /**
     * Show modal variable
     *
     * @var boolean
     */
    public bool $show = true;

    /**
     * Render
     *
     * @return View
     */
    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.livewire-modal'));
    }

    /**
     * Close modal
     * 
     * @return void
     */
    public function close()
    {
        $this->show = false;
    }
}
