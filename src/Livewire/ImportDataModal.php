<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use LivewireUI\Modal\ModalComponent;
use Illuminate\Support\Facades\Storage;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class ImportDataModal extends ModalComponent
{
    use WithFileUploads;

    public $manageableModelClass;

    #[Rule(['required', 'mimes:csv'])]
    public $file;

    public function updatedFile()
    {
        if (!$this->getErrorBag()->isEmpty()) {
            // Delete the temporary file if validation fails
            if ($this->file) {
                // Delete the temporary file from the Storage livewire-tmp folder
                Storage::disk('local')->delete('livewire-tmp/' . $this->file->getFilename());
            }
            $this->file = null; // Clear the file property
        }
    }

    public function mount(string $manageableModelClass)
    {
        // Broadcast that modal has opened successfully
        $this->dispatch('import-data-modal.opened');

        // Set manageable model
        $this->manageableModelClass = $manageableModelClass;
    }

    public static function behavior(): array
    {
        return [
            // Close the modal if the escape key is pressed
            'close-on-escape' => true,
            // Close the modal if someone clicks outside the modal
            'close-on-backdrop-click' => true,
            // Trap the users focus inside the modal (e.g. input autofocus and going back and forth between input fields)
            'trap-focus' => true,
            // Remove all unsaved changes once someone closes the modal
            'remove-state-on-close' => false,
        ];
    }

    public static function attributes(): array
    {
        return [
            // Set the modal size to 2xl, you can choose between:
            // xs, sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl, fullscreen
            'size' => 'fullscreen',
        ];
    }

    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.import-data-modal'), [

        ]);
    }
}