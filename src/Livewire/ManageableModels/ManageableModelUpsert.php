<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Class ManageableModelUpsert
 *
 * This class is responsible for handling the upsert (create/update) functionality of manageable models.
 */
class ManageableModelUpsert extends Component
{
    /* Properties
    --------------------------------------------------------------------------*/

    /**
     * Manageable model instance
     *
     * @var ManageableModel
     */
    private $manageableModel;

    /**
     * The manageable model class
     *
     * @var string
     */
    public $manageableModelClass;

    /**
     * The model id
     *
     * @var ?int
     */
    public ?int $modelId = null;

    /**
     * Form fields
     */
    public $formFields = [];

    /* Livewire Methods / Hooks
    --------------------------------------------------------------------------*/

    /**
     * Mount the component.
     *
     * @param string $manageableModelClass The fully qualified class name of the manageable model.
     * @param mixed $modelId The ID of the model being upserted.
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function mount($manageableModelClass, $modelId = null)
    {
        // If the manageable model reference is null, redirect to the dashboard
        if (is_null($manageableModelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model `$manageableModelClass` not found.");
        }

        // Set the manageable model class and model id
        $this->manageableModelClass = $manageableModelClass;
        $this->modelId = $modelId;

        // Get the manageable model and base model class
        $manageableModel = new $manageableModelClass();
        $modelClass = $manageableModelClass::getBaseModelClass();

        // If the model class does not exist, redirect to the dashboard
        if (!class_exists($modelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Model `$modelClass` not found while loading manageable model `$manageableModelClass`.");
        }

        // If the model ID is null, create a new model instance
        if (is_null($modelId)) {
            $manageableModel->setModelInstance(new $modelClass());
        } else {
            // Find the model by its ID
            $manageableModel->setModelInstance($modelClass::find($modelId));

            // Set form fields from the model instance
            $this->formFields = $manageableModel->getFormFieldsKeyValues();

            // If the model is null, redirect to the dashboard
            if (is_null($manageableModel->modelInstance)) {
                return redirect()->route('wrla.dashboard')->with('error', "Model `$modelClass` with ID `$modelId` not found.");
            }
        }

        // Set the manageable model property
        $this->manageableModel = $manageableModel;
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view(WRLAHelper::getViewPath('livewire.manageable-models.upsert'), [
            'manageableModel' => $this->manageableModel,
        ]);
    }

    /* Methods
    --------------------------------------------------------------------------*/

    /**
     * Save the model.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save()
    {
        // Check model class exists
        if (is_null($this->manageableModelClass) || !class_exists($this->manageableModelClass)) {
            return redirect()->route('wrla.dashboard')->with('error', "Manageable model `$this->manageableModelClass` not found.");
        }

        if($this->modelId != null)
        {
            // Get model by it's id
            $manageableModel =  $this->manageableModelClass::getByInstanceId($this->modelId);

            // Check model id exists
            if ($manageableModel == null) {
                return redirect()->route('wrla.dashboard')->with('error', "Model ".$this->manageableModelClass." with ID `$this->modelId` not found.");
            }
        }
        else
        {
            // Create new model instance
            $manageableModel = new $this->manageableModelClass();
        }

        // Get validation rules for this model
        $rules = $manageableModel->getValidationRules()->toArray();
        // Prepend formFields to each key
        $rules = array_combine(
            array_map(function($key) {
                return 'formFields.'.$key;
            }, array_keys($rules)),
            $rules
        );
        // Validate
        $this->validate($rules);

        // Update only changed values on the model instance
        $manageableModel->updateModelInstanceProperties($this->formFields);

        // Save the model
        $manageableModel->modelInstance->save();

        // Redirect to the browse page
        return redirect()->route('wrla.manageable-model.browse', ['modelUrlAlias' => $manageableModel->getUrlAlias()]);
    }
}
