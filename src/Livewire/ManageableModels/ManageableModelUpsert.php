<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use Livewire\WithFileUploads;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Enums\PageType;

/**
 * Class ManageableModelUpsert
 *
 * This class represents a Livewire component for upserting a manageable model.
 */
class ManageableModelUpsert extends Component
{
    /* Traits
    --------------------------------------------------------------------------*/
    use WithFileUploads;

    /* Properties
    --------------------------------------------------------------------------*/

    /**
     * The class name of the manageable model.
     */
    public string $manageableModelClass;

    /**
     * Livewire fields, attach with manageable model ->setAttribute('wire:model.live', 'livewireData.key')
     */
    public array $livewireData = [];

    /**
     * Number of renders counter
     */
    public int $numberOfRenders = 0;

    /**
     * Refresh manageable field values
     */
    public bool $refreshManageableFields = false;

    /**
     * Upsert type
     */
    public PageType $upsertType;

    /**
     * Model id, null if creating a new model.
     */
    public ?int $modelId = null;

    /**
     * Override title
     */
    public ?string $overrideTitle = null;

    /* Livewire Methods / Hooks
    --------------------------------------------------------------------------*/

    public $listeners = [
        'wrla_upsert_refresh' => '$refresh',
        'deleteModel' => 'deleteModel',
    ];

    /**
     * Mount the component.
     *
     * @param  string  $manageableModelClass  The class name of the manageable model.
     * @param  PageType  $upsertType  The type of upsert page.
     * @param  ?int  $modelId  The id of the model to upsert, null if creating a new model.
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function mount(string $manageableModelClass, PageType $upsertType, ?int $modelId = null, ?string $overrideTitle = null)
    {
        // If the manageable model reference is null, redirect to the dashboard
        if (is_null($manageableModelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model `$manageableModelClass` not found.");
        }

        // Get the manageable model and base model class
        $this->manageableModelClass = $manageableModelClass;
        $manageableModelInstance = $this->getModelInstance();
        $modelClass = $manageableModelInstance::getBaseModelClass();

        // If the model class does not exist, redirect to the dashboard
        if (! class_exists($modelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Model `$modelClass` not found while loading manageable model `$manageableModelClass`.");
        }

        // Set other properties
        $this->modelId = $modelId;
        $this->upsertType = $upsertType;
        $this->overrideTitle = $overrideTitle;

        // Set page type
        WRLAHelper::setCurrentPageType($this->upsertType);
        WRLAHelper::setCurrentActiveManageableModelClass($this->manageableModelClass);
    }

    /**
     * Set field value (Livewire method)
     *
     * @param  string  $field  Field name
     * @param  mixed  $value  Field value
     */
    public function setFieldValue(string $field, mixed $value)
    {
        $this->livewireData[$field] = $value;
        $this->refreshManageableFields = true;
    }

    /**
     * Set field values (Livewire method)
     *
     * @param  array  $fieldKeyValues  Field key values
     */
    public function setFieldValues(array $fieldKeyValues)
    {
        foreach ($fieldKeyValues as $field => $value) {
            $this->livewireData[$field] = $value;
        }
        $this->refreshManageableFields = true;
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        try {
            // Get manageable model and fields data
            $manageableModel = $this->manageableModelClass::make($this->modelId);
            ManageableModel::$livewireFields = $this->livewireData;
            $manageableFields = $manageableModel->getManageableFieldsFinal();

            // Set page type
            WRLAHelper::setCurrentPageType($this->upsertType);
            WRLAHelper::setCurrentActiveManageableModelClass($this->manageableModelClass);
            WRLAHelper::setCurrentActiveManageableModelInstance($manageableModel);

            // If first render,set default livewire field values
            $usesLivewireFields = false;
            if ($this->numberOfRenders === 0) {
                foreach ($manageableFields as $manageableField) {
                    if ($manageableField->isModeledWithLivewire()) {
                        $manageableField->render(); // This allows for fields like JSON that modify the rendered value
                        $this->livewireData[$manageableField->getAttribute('name')] = $manageableField->getValue();
                        $usesLivewireFields = true;
                    }
                }
            }

            if ($usesLivewireFields) {
                ManageableModel::$livewireFields = $this->livewireData;
                $manageableFields = $manageableModel->getManageableFieldsFinal();
            }

            // Increment number of renders
            $this->numberOfRenders++;

            // If force refresh manageable fields, set field values
            if ($this->refreshManageableFields) {
                foreach ($manageableFields as $manageableField) {
                    if ($manageableField->isModeledWithLivewire()) {
                        $manageableField->setAttribute('value', $this->livewireData[$manageableField->getAttribute('name')]);
                    }
                }
            }

            // Render the view
            return view(WRLAHelper::getViewPath('livewire.manageable-models.upsert'), [
                'manageableModel' => $manageableModel,
                'upsertType' => $this->upsertType,
                'usesWysiwyg' => $manageableModel->usesWysiwyg(),
                'manageableFields' => $manageableFields,
                'numberOfRenders' => $this->numberOfRenders,
                'overrideTitle' => $this->overrideTitle,
            ]);
        } catch (\Exception $e) {
            // If an error occurs, redirect to the dashboard with an error message
            redirect()->route('wrla.dashboard')->with('error', "Error loading manageable model `$this->manageableModelClass`: ".$e->getMessage());

            return '<div></div>';
        }
    }

    /**
     * Delete a model.
     *
     * @param  string  $modelUrlAlias  The URL alias of the model to delete.
     * @param  int  $id  The ID of the model to delete.
     */
    public function deleteModel(string $modelUrlAlias, int $id)
    {
        // Get manageable model instance
        $manageableModel = new $this->{'manageableModelClass'}($id);

        // Check that model URL alias matches the manageable model class URL alias
        if ($modelUrlAlias != $this->manageableModelClass::getUrlAlias()) {
            $this->addError('error', 'Model URL alias does not match manageable model class URL alias.');

            return;
        }

        // Delete the model and deconstruct the response
        [$success, $message] = WRLAHelper::deleteModel($manageableModel, $id);

        // If model failed to delete, add an error
        if (! $success) {
            $this->addError('error', $message);

            return;
        }

        // Otherwise the model was deleted successfully, redirect to browse page for the model
        session()->flash('success', $message);

        return redirect()->route('wrla.manageable-models.browse', [
            'modelUrlAlias' => $this->manageableModelClass::getUrlAlias(),
        ]);
    }

    /* Methods
    --------------------------------------------------------------------------*/

    /**
     * Get manageable model instance
     */
    public function getModelInstance(): ManageableModel
    {
        return new $this->manageableModelClass;
    }

    /**
     * Call manageable model action.
     */
    public function callManageableModelAction(int $instanceId, string $actionKey) {
        WRLAHelper::callManageableModelAction($this, $this->manageableModelClass, $instanceId, $actionKey);
    }
}
