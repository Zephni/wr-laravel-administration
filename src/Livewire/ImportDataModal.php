<?php

namespace WebRegulate\LaravelAdministration\Livewire;

use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;
use LivewireUI\Modal\ModalComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportRedirects\Redirector;
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
        'wrlaTmpFilePath' => '',
        'previewRowsMax' => 8,
        'currentStep' => 1, // int or 'completed'
        'origionalHeaders' => [],
        'headers' => [],
        'origionalRows' => [],
        'rows' => [],
        'tableColumns' => [],
        'totalRows' => 0,
        'successfullImports' => 0,
        'failedImports' => 0,
        'failedReasons' => [],
        'totalImports' => 0,
        'totalImported' => 0,
    ];

    /**
     * Debug information.
     *
     * @var array
     */
    public array $debugInfo;

    /**
     * Listen for the process-next-batch event.
     *
     * @return void
     */
    protected $listeners = ['process-next-batch' => 'processBatch'];

    // Modal config
    public static function modalMaxWidth(): string { return '7xl'; }

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

        // Store file (that we will progressivly read and remove rows from later), and remove tmp file
        // File name should be tablename-import-Y-m-d-H-i-s.csv
        $fileName = (new ($this->manageableModelClass::getBaseModelClass()))->getTable() . '-import-' . now()->format('Y-m-d-H-i-s') . '.csv';
        $this->data['wrlaTmpFilePath'] = $this->file->storeAs('wrla-tmp', $fileName);
        Storage::disk('local')->delete('livewire-tmp/' . $this->file->getFilename());

        // Get the real path of the uploaded file and read its contents
        $file = file(storage_path('app/'.$this->data['wrlaTmpFilePath']));
        $this->data['totalRows'] = count($file) - 1;
        $fileData = array_map('str_getcsv', array_slice($file, 0, 101));

        // Clean the extracted data and seperate headers from rows
        [$origionalHeaders, $originalRows] = $this->cleanAllFileData($fileData);

        // Extract headers and rows from the CSV file
        $this->data['origionalHeaders'] = $origionalHeaders;
        $this->data['origionalRows'] = $originalRows;

        // Set headers and rows
        $this->data['headers'] = $this->data['origionalHeaders'];
        $this->data['rows'] = $this->data['origionalRows'];

        // Get the columns of the manageable model's table, excluding the 'id', 'created_at', 'updated_at' and 'deleted_at' columns
        $manageableModel = (new $this->manageableModelClass)->getModelInstance();
        $manageableModelColumns = Schema::getColumnListing($manageableModel->getTable());
        $manageableModelColumns = array_diff($manageableModelColumns, ['id', 'created_at', 'updated_at', 'deleted_at']);
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
     * Renders the view for the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $previewRows = array_slice($this->data['rows'], 0, $this->data['previewRowsMax']);
        return view(WRLAHelper::getViewPath('livewire.import-data-modal'), [
            'previewRows' => $previewRows
        ]);
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
        $this->data['totalImported'] = 0;
        $this->data['currentStep'] = 'processing';

        // Temp
        $this->dispatch('process-next-batch');
    }

    /**
     * Process a batch of data.
     *
     * @return void
     */
    public function processBatch(): void
    {
        $modelClass = (new $this->manageableModelClass)->getBaseModelClass();
        $batchSize = 100;

        // Note that the array_splice function will modify the original array (passed by reference)
        $batchData = array_splice($this->data['rows'], 0, $batchSize);

        if (!empty($batchData)) {
            $formattedBatchData = [];
            foreach ($batchData as $row) {
                $rowData = [];
                foreach ($this->headersMappedToColumns as $index => $column) {
                    if (!empty($column)) {
                        $rowData[$column] = $row[$index];
                    }
                }

                $formattedBatchData[] = $rowData;
            }

            // Insert batch and update total imported
            $this->insertBatch($modelClass, $formattedBatchData);
            $this->data['totalImported'] += count($formattedBatchData);

            // Trigger the next batch processing
            $this->dispatch('process-next-batch');
        } else {
            $this->data['currentStep'] = 'completed';
        }
    }

    private function insertBatch($modelClass, $batchData)
    {
        try {
            $modelClass::insert($batchData);
            $this->data['successfullImports'] += count($batchData);
        } catch (\Exception $e) {
            $this->data['failedImports'] += count($batchData);
            if (count($this->data['failedReasons']) < 5) {
                $this->data['failedReasons'][] = $e->getMessage();
            }
        }
    }

    /**
     * Close and refresh
     *
     * @return void
     */
    public function closeAndRefresh()
    {
        // Close the modal
        $this->closeModal();

        // Refresh current URL
        $this->js('window.location.reload()');
    }

    /**
     * Cleans all data (headers and rows) from the provided file data.
     *
     * @param array $fileData
     * @return void
     */
    public function cleanAllFileData(array $fileData): array
    {
        // Extract headers and rows from the file data
        $headers = array_shift($fileData);
        $rows = $fileData;

        // Clean headers by trimming whitespace and removing unwanted characters
        foreach ($headers as $key => $value) {
            $value = trim((string) $value);
            $value = preg_replace('/^\x{FEFF}/u', '', $value);
            $headers[$key] = $value;
            if ($headers[$key] == 'id') {
                unset($headers[$key]);
            }
        }

        // Clean rows by trimming whitespace and removing unwanted characters
        foreach ($rows as $rowKey => $row) {
            foreach ($row as $key => $value) {
                $value = trim((string) $value);
                $value = preg_replace('/^\x{FEFF}/u', '', $value);
                $rows[$rowKey][$key] = $value;
            }
        }

        return [
            $headers,
            $rows
        ];
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
