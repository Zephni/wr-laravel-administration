<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use LivewireUI\Modal\ModalComponent;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class ImportDataModal extends ModalComponent
{
    use WithFileUploads;

    public $manageableModelClass;

    #[Rule(['required', 'file', 'mimes:csv', 'max:5120'])]
    public $file = null;

    public array $data = [
        'headers' => [],
        'rows' => [],
        'tableColumns' => [],
        'headersMappedToColumns' => [],
    ];

    public array $debugInfo;

    /**
     * Updated file hook, runs after attribute validation
     * 
     * @return void
     */
    public function updatedFile()
    {
        // If any errors
        if (!$this->getErrorBag()->isEmpty()) {
            // Delete the temporary file if validation fails
            if ($this->file) {
                Storage::disk('local')->delete('livewire-tmp/' . $this->file->getFilename());
            }

            // Clear the file property
            $this->file = null;
            return;
        }

        // If valid, we now need to check the CSV file is valid, if not, manually add an error
        $filePath = $this->file->getRealPath();
        $fileData = array_map('str_getcsv', file($filePath));

        // Set first row as headers
        $this->data['headers'] = array_shift($fileData);

        // Set row data
        $this->data['rows'] = $fileData;

        // Clean all data
        $this->cleanAllData();

        // Get all manageable model columns and set to tableColumns
        $manageableModel = (new $this->manageableModelClass)->getModelInstance();
        $manageableModelColumns = Schema::getColumnListing($manageableModel->getTable());
        $manageableModelColumns = array_diff($manageableModelColumns, ['id']);
        $this->data['tableColumns'] = array_combine($manageableModelColumns, $manageableModelColumns);
        $this->data['tableColumns'] = ['__wrla_unset_column__' => '❌ Select a column'] + $this->data['tableColumns'];

        // Do best we can at mapping keys to columns
        $this->autoMapHeadersToColumns();

        $this->debugInfo = $this->data;
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

    /**
     * Cleans all data (Headers and Rows) from the currently set data
     * 
     * @return void
     */
    public function cleanAllData()
    {
        
        // Clean headers
        foreach ($this->data['headers'] as $key => $value) {
            // Trim
            $this->data['headers'][$key] = trim($value);

            // Remove \u{FEFF} (UTF-8 BOM) from the start of the string
            $this->data['headers'][$key] = preg_replace('/^\x{FEFF}/u', '', $this->data['headers'][$key]);

            // Remove all special characters but allow spaces
            $this->data['headers'][$key] = preg_replace('/[^A-Za-z0-9 ]/', '', $this->data['headers'][$key]);
        }

        // Clean rows
        foreach ($this->data['rows'] as $rowKey => $row) {
            foreach ($row as $key => $value) {
                // Trim
                $this->data['rows'][$rowKey][$key] = trim($value);

                // Remove all special characters but allow spaces
                $this->data['rows'][$rowKey][$key] = preg_replace('/[^A-Za-z0-9 ]/', '', $this->data['rows'][$rowKey][$key]);
            }
        }
    }

    public function autoMapHeadersToColumns()
    {
        // Loop through all map keys to columns
        foreach ($this->data['headers'] as $headerIndex => $header) {
            $this->data['headersMappedToColumns']["index_$headerIndex"] = null;

            // Loop through all table columns
            foreach ($this->data['tableColumns'] as $actualColumn) {
                $header = str($header)->lower()->replace(' ', '_')->__toString();

                // If the example column is similar to the table column
                if (str($actualColumn) == $header) {
                    // Set the map key to column
                    $this->data['headersMappedToColumns']["index_$headerIndex"] = $actualColumn;
                }
            }
        }

        // Force re-render
        $this->render();
    }
}