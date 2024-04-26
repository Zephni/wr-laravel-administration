<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
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
    public function updateModelInstanceProperties(array $formFields): void
    {
        $manageableFields = $this->getManageableFields();

        foreach ($manageableFields as $manageableField) {
            $fieldName = $manageableField->attribute('name');

            if (array_key_exists($fieldName, $formFields)) {
                $fieldValue = $formFields[$fieldName];

                if ($this->modelInstance->$fieldName != $fieldValue) {
                    $this->modelInstance->$fieldName = $fieldValue;
                }
            }
        }
    }
}
