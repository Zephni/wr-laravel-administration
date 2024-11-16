<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;
use LivewireUI\Modal\ModalComponent;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Class ImportDataModal
 * Handles the import of CSV data into a manageable model.
 */
class ImportDataModal extends ModalComponent
{
    use WithFileUploads;

    /**
     * The class name of the manageable model.
     * 
     * @var string
     */
    public $manageableModelClass;

    /**
     * The uploaded file.
     * 
     * @var mixed
     */
    #[Rule(['required', 'file', 'mimes:csv', 'max:5120'])]
    public $file = null;

    /**
     * Headers mapped to columns
     * 
     * @var array
     */
    public array $headersMappedToColumns = [];

    /**
     * Data related to the CSV file.
     * 
     * @var array
     */
    public array $data = [
        'previewRowsMax' => 8,
        'currentStep' => 1,
        'origionalHeaders' => [],
        'headers' => [],
        'origionalRows' => [],
        'rows' => [],
        'tableColumns' => [],
    ];

    /**
     * Debug information.
     * 
     * @var array
     */
    public array $debugInfo;

    /**
     * Hook that runs after the file attribute is validated.
     * 
     * @return void
     */
    public function updatedFile()
    {
        // If there are validation errors, delete the uploaded file and reset the file attribute
        if (!$this->getErrorBag()->isEmpty()) {
            if ($this->file) {
                Storage::disk('local')->delete('livewire-tmp/' . $this->file->getFilename());
            }
            $this->file = null;
            return;
        }

        // Get the real path of the uploaded file and read its contents
        $filePath = $this->file->getRealPath();
        $fileData = array_map('str_getcsv', file($filePath));

        // Extract headers and rows from the CSV file
        $this->data['origionalHeaders'] = array_shift($fileData);
        $this->data['origionalRows'] = $fileData;

        // Clean the extracted data
        $this->cleanAllOriginalData();

        // Set headers and rows
        $this->data['headers'] = $this->data['origionalHeaders'];
        $this->data['rows'] = $this->data['origionalRows'];

        // Get the columns of the manageable model's table, excluding the 'id' column
        $manageableModel = (new $this->manageableModelClass)->getModelInstance();
        $manageableModelColumns = Schema::getColumnListing($manageableModel->getTable());
        $manageableModelColumns = array_diff($manageableModelColumns, ['id']);
        $this->data['tableColumns'] = array_combine($manageableModelColumns, $manageableModelColumns);
        $this->data['tableColumns'] = ['' => ' - no column selected - '] + $this->data['tableColumns'];

        // Automatically map headers to columns
        $this->autoMapHeadersToColumns();

        // Advance current step
        $this->data['currentStep'] = 2;
    }

    /**
     * Hook that runs after mapping header to a columns field is updated.
     * 
     * @return void
     */
    public function updatedHeadersMappedToColumns()
    {
        $this->alignHeadersAndRowsWithMappedColumns();
    }

    /**
     * Align rows with mapped columns
     * 
     * @return void
     */
    public function alignHeadersAndRowsWithMappedColumns()
    {
        // Shift headers and rows based on mapping column index to column name
        $this->data['headers'] = [];
        $this->data['rows'] = [];

        // Headers
        foreach ($this->data['origionalHeaders'] as $index => $header) {
            // If the header is not mapped to a column, skip the header
            if (empty($this->headersMappedToColumns["$index"])) {
                continue;
            }

            $this->data['headers'][] = $header;
        }

        // Rows
        foreach ($this->data['origionalRows'] as $row) {
            $newRow = [];

            foreach ($row as $index => $value) {
                // If the header is not mapped to a column, skip the value
                if (empty($this->headersMappedToColumns["$index"])) {
                    continue;
                }

                $newRow[$index] = $value;
            }

            $this->data['rows'][] = $newRow;
        }

        // dd(
        //     $this->data['headers'],
        //     $this->data['rows']
        // );
    }


    /**
     * Initializes the component with the given manageable model class.
     * 
     * @param string $manageableModelClass
     * @return void
     */
    public function mount(string $manageableModelClass)
    {
        // Dispatch an event indicating that the modal has been opened
        $this->dispatch('import-data-modal.opened');
        $this->manageableModelClass = $manageableModelClass;
    }

    /**
     * Defines the behavior of the modal component.
     * 
     * @return array
     */
    public static function behavior(): array
    {
        return [
            'close-on-escape' => true,
            'close-on-backdrop-click' => true,
            'trap-focus' => true,
            'remove-state-on-close' => false,
        ];
    }

    /**
     * Defines the attributes of the modal component.
     * 
     * @return array
     */
    public static function attributes(): array
    {
        return [
            'size' => 'fullscreen',
        ];
    }

    /**
     * Renders the view for the component.
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.import-data-modal'), []);
    }

    /**
     * Go to step X
     * 
     * @param int $step
     * @return void
     */
    public function goToStep(int $step)
    {
        $this->data['currentStep'] = $step;
    }

    /**
     * Import data
     * 
     * @return void
     */
    public function importData(): void
    {
        dd(
            $this->data['headers'],
            $this->data['rows']
        );
    }

    /**
     * Cleans all data (headers and rows) from the currently set data.
     * 
     * @return void
     */
    public function cleanAllOriginalData()
    {
        // Clean headers by trimming whitespace and removing unwanted characters
        foreach ($this->data['origionalHeaders'] as $key => $value) {
            $this->data['origionalHeaders'][$key] = trim($value);
            $this->data['origionalHeaders'][$key] = preg_replace('/^\x{FEFF}/u', '', $this->data['origionalHeaders'][$key]);
            $this->data['origionalHeaders'][$key] = preg_replace('/[^A-Za-z0-9 ]/', '', $this->data['origionalHeaders'][$key]);
        }

        // Clean rows by trimming whitespace and removing unwanted characters
        foreach ($this->data['origionalRows'] as $rowKey => $row) {
            foreach ($row as $key => $value) {
                $this->data['origionalRows'][$rowKey][$key] = trim($value);
            }
        }
    }

    /**
     * Automatically maps headers to columns.
     * 
     * @return void
     */
    public function autoMapHeadersToColumns()
    {
        // Already mapped
        $alreadyMappedColoumns = [];

        // Initialize the mapping of headers to columns
        foreach ($this->data['headers'] as $headerIndex => $header) {
            $this->headersMappedToColumns["$headerIndex"] = null;

            // Attempt to map each header to a corresponding column
            foreach ($this->data['tableColumns'] as $actualColumn) {
                $header = str($header)->lower()->replace(' ', '_')->__toString();

                if (!in_array($actualColumn, $alreadyMappedColoumns) && str($actualColumn) == $header) {
                    $this->headersMappedToColumns["$headerIndex"] = $actualColumn;
                    $alreadyMappedColoumns[] = $actualColumn;
                }
            }
        }

        // Align rows with mapped columns
        $this->alignHeadersAndRowsWithMappedColumns();
    }
}