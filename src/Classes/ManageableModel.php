<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\FormComponents\Hidden;

class ManageableModel
{
    /**
     * Model that this manageable model is based on, eg. \App\Models\User::class.
     *
     * @var string|null
     */
    private static $baseModelClass = null;

    /**
     * Base model instance
     *
     * @var mixed
     */
    private $modelInstance = null;

    /**
     * Collection of manageable models, pushed to in the register method which is called within the serice provider.
     *
     * @var ?Collection
     */
    public static ?Collection $manageableModels = null;

    /**
     * Cache
     *
     * @var array
     */
    public static array $cache = [
        'isSoftDeletable' => null
    ];

    /**
     * Register the manageable model.
     *
     * @return void
     */
    public static function register(): void
    {
        // If manageable models is null, set it to a collection
        if (is_null(self::$manageableModels)) {
            self::$manageableModels = collect();
        }

        // Register the model
        self::$manageableModels->push(static::class);
    }

    /**
     * If model instance is passed (must be an instance of the base model), set it as the model instance.
     * If model ID is passed, get the model instance by ID.
     * Otherwise, set the model instance to a new instance of the base model.
     *
     * @param mixed|int|null $modelInstance
     */
    public function __construct($modelInstanceOrId = null)
    {
        // If model instance (extends base model) is passed, set it as the model instance
        if ($modelInstanceOrId instanceof static::$baseModelClass) {
            $this->setModelInstance($modelInstanceOrId);
        // If model ID is passed, get the model instance by ID
        } elseif (is_numeric($modelInstanceOrId)) {
            $this->setModelInstance(static::$baseModelClass::find($modelInstanceOrId));
        // Otherwise, set the model instance to a new instance of the base model
        } else {
            $this->setModelInstance(new static::$baseModelClass);
        }
    }

    /**
     * Make, a static version of the constructor
     *
     * @param mixed|int|null $modelInstance
     * @return static
     */
    public static function make($modelInstanceOrId = null): static
    {
        return new static($modelInstanceOrId);
    }

    /**
     * Set the base model instance.
     *
     * @param mixed $modelInstance
     * @return ManageableModel
     */
    public function setModelInstance($modelInstance): ManageableModel
    {
        $this->modelInstance = $modelInstance;
        return $this;
    }

    /**
     * Get the base model instance.
     *
     * @return mixed
     */
    public function getModelInstance(): mixed
    {
        return $this->modelInstance;
    }

    /**
     * Get manageable model by model class.
     *
     * @param string $modelClass
     * @return mixed
     */
    public static function getByModelClass(string $modelClass): mixed
    {
        return self::$manageableModels->first(function ($manageableModel) use ($modelClass) {
            return $manageableModel::$baseModelClass === $modelClass;
        });
    }

    /**
     * Get manageable model by URL alias.
     *
     * @param string $urlAlias
     * @return mixed
     */
    public static function getByUrlAlias(string $urlAlias): mixed
    {
        $manageableModel = self::$manageableModels->first(function ($manageableModel) use ($urlAlias) {
            return $manageableModel::getUrlAlias() === $urlAlias;
        });

        return $manageableModel;
    }

    /**
     * Get the base model for the manageable model.
     *
     * @return string
     */
    public static function getBaseModelClass(): string
    {
        return static::$baseModelClass;
    }

    /**
     * Get the URL alias for the manageable model.
     *
     * @return string
     */
    public static function getUrlAlias(): string
    {
        return 'manageable-model';
    }

    /**
     * Get the display name for the manageable model.
     *
     * @return string
     */
    public static function getDisplayName(): Stringable
    {
        return str('Manageable Model');
    }

    /**
     * Get the icon for the manageable model.
     *
     * @return string
     */
    public static function getIcon(): string
    {
        return 'fa fa-question';
    }

    /**
     * Get browse actions
     *
     * @param mixed $model
     * @return Collection
     */
    public static function getBrowseActions(mixed $model): Collection {
        $manageableModel = static::make($model);

        $browseActions = collect();

        // If model doesn't have soft delets and not trashed
        if(!self::isSoftDeletable() && !$model->trashed()) {
            $browseActions->put('edit', view(WRLAHelper::getViewPath('components.browse-actions.edit-button'), [
                'manageableModel' => $manageableModel
            ]));

            $browseActions->put('delete', view(WRLAHelper::getViewPath('components.browse-actions.delete-button'), [
                'manageableModel' => $manageableModel
            ]));
        // If trashed
        } else {
            $browseActions->put('restore', view(WRLAHelper::getViewPath('components.browse-actions.restore-button'), [
                'manageableModel' => $manageableModel
            ]));

            $browseActions->put('delete', view(WRLAHelper::getViewPath('components.browse-actions.delete-button'), [
                'manageableModel' => $manageableModel,
                'text' => 'Permanent Delete',
                'permanent' => true
            ]));
        }

        return $browseActions;
    }

    /**
     * Virtual get manageable fields method.
     *
     * @return Collection
     */
    public function getManageableFields(): Collection
    {
        return collect();
    }

    /**
     * Get validation rules
     *
     * @return array
     */
    public function getValidationRules(): Collection
    {
        $manageableFields = $this->getManageableFields();

        $validationRules = collect();

        foreach ($manageableFields as $manageableField) {
            $validationRules->put($manageableField->attribute('name'), $manageableField->validationRule);
        }

        return $validationRules;
    }

    /**
     * Get form fields values array
     *
     * @return array
     */
    public function getFormFieldsKeyValues(): array
    {
        $manageableFields = $this->getManageableFields();

        $formFieldsValues = [];

        foreach ($manageableFields as $manageableField) {
            $formFieldsValues[$manageableField->attribute('name')] = $manageableField->attribute('value');
        }

        return $formFieldsValues;
    }

    /**
     * Update model instance from form fields, only update values that haven't changed
     *
     * @param array $formFields
     */
    public function updateModelInstanceProperties(Request $request, Collection $formComponents, array $formKeyValues): void
    {
        $this->postUpdateModelInstance($request);

        $manageableFields = $this->getManageableFields();

        foreach ($manageableFields as $manageableField) {
            $fieldName = $manageableField->attribute('name');

            if (array_key_exists($fieldName, $formKeyValues)) {
                $formComponent = $formComponents->first(function ($formComponent) use ($fieldName) {
                    return $formComponent->attribute('name') === $fieldName;
                });

                $fieldValue = $formComponent->applyValue($formKeyValues[$fieldName]);

                $this->modelInstance->$fieldName = $fieldValue;
            }
        }
    }

    /**
     * Post update model instance hook
     *
     * @return void
     */
    public function postUpdateModelInstance(Request $request): void
    {
        // Override this method in your model to add custom logic after updating the model instance
    }

    /**
     * Is model soft deletable
     *
     * @return bool
     */
    public static function isSoftDeletable(): bool
    {
        if(static::$cache['isSoftDeletable'] == null) {
            $model = new static::$baseModelClass;
            static::$cache['isSoftDeletable'] = method_exists($model, 'trashed');
        }

        return static::$cache['isSoftDeletable'];
    }
}
