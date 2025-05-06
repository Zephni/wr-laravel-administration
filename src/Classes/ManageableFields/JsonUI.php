<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Support\Arr;
use function PHPSTORM_META\type;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

use WebRegulate\LaravelAdministration\Traits\ManageableField;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class JsonUI
{
    use ManageableField;

    /**
     * Levels nested
     * @var int
     */
    private int $levelsNested = 0;

    /**
     * Default key values to merge with nested.key values.
     *
     * @var array
     */
    protected array $defaultKeyValues = [];

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param ?ManageableModel $manageableModel
     * @param ?mixed $column
     * @param ?array $fieldSettings
     * @param ?array $options bool allowCreate (true)
     * @return static
     */
    public static function make(?ManageableModel $manageableModel, ?string $column, ?array $fieldSettings = null, ?array $options = null): static
    {
        $manageableField = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);

        $options['fieldSettings'] = $fieldSettings;

        if(!is_null($options)) {
            $manageableField->setOptions(array_merge([
                'allowCreate' => true,
                'debug' => false,
            ], $options));
        }

        return $manageableField;
    }

    /**
     * Post constructed method, called after name and value attributes are set.
     *
     * @return $this
     */
    public function postConstructed(): static
    {
        $this->validation('json');

        return $this;
    }

    /**
     * Merge default keys and nested.key values before render.
     *
     * @param array $defaultKeyValues
     * @return $this
     */
    public function mergeDefaultKeyValues(array $defaultKeyValues): static
    {
        $this->defaultKeyValues = $defaultKeyValues;
        return $this;
    }

    /**
     * Json format validation. Accepts a list of keys or nested.keys and their validation rules.
     *
     * @param array $rules
     * @return $this
     */
    public function jsonFormatValidation(array $rules): static
    {
        $this->inlineValidation(fn($value) => WRLAHelper::jsonFormatValidation($value, $rules));

        return $this;
    }

    /**
     * Calculated value
     *
     * @param mixed $value
     * @return string
     */
    public function calculatedValue(mixed $value): string
    {
        // If value is empty string we are likely creating a new record, so we set it to value json so
        // that we can apply default key values
        if($value === '') {
            $value = '{}';
        }

        // If $value is array, convert to json first
        if(is_array($value)) {
            $value = json_encode($value);
        }

        // Check if json is valid, if not then do not format it and show as is
        $jsonData = json_decode((string) $value) ?? false;

        if($jsonData !== false) {
            // Now we have validation, we can loop through the merge default key values and apply them
            foreach($this->defaultKeyValues as $key => $value) {
                if(data_get($jsonData, $key) === null) {
                    data_set($jsonData, $key, $value);
                }
            }
        }

        $value = json_encode($jsonData);

        // If value is just an empty array '[]', we instead set it to an empty object '{}'
        if($value === '[]') $value = '{}';

        return trim($value);
    }

    /**
     * Render the input field.
     *
     * @return mixed
     */
    public function render(): mixed
    {
        // Get name
        $name = $this->getName();

        // Check if value is set in old() and set if so
        if(old($name) !== null) {
            $value = old($name);
            $this->setValue($value);
        } else {
            $value = $this->getValue();
        }

        // Get / set calculated value
        $value = $this->calculatedValue($this->getValue());
        $this->setValue($value);

        // Get decoded JSON data
        $jsonData = json_decode($value, true);

        // Render view
        return view(WRLAHelper::getViewPath('components.forms.json-ui'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'jsonData' => $jsonData,
            'attributes' => Arr::toAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => $this->getValue(),
                'type' => $this->getAttribute('type') ?? 'text',
            ])),

        ])->render();
    }
}
