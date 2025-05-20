<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Support\Arr;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Traits\ManageableField;

class JsonUI
{
    use ManageableField;

    /**
     * Levels nested
     */
    private int $levelsNested = 0;

    /**
     * Default key values to merge with nested.key values.
     */
    protected array $defaultKeyValues = [];

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param  ?mixed  $column
     * @param  ?array  $options  bool allowCreate (true)
     */
    public static function make(?ManageableModel $manageableModel, ?string $column, ?array $fieldSettings = null, ?array $options = null): static
    {
        $manageableField = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);

        $options['fieldSettings'] = $fieldSettings;

        if (! is_null($options)) {
            $manageableField->setOptions(array_merge([
                'allowCreate' => true,
                'debug' => false,
                'hideKeyValues' => [],
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
     * @return $this
     */
    public function mergeDefaultKeyValues(array $defaultKeyValues): static
    {
        $this->defaultKeyValues = $defaultKeyValues;

        return $this;
    }

    /**
     * Hide key values from the json data.
     *
     * @param  array  $dottedKeys  Array of dotted keys to hide. eg. ['*.some_key.*.some_key.*', ...]
     * @return $this
     */
    public function hideKeyValues(array $dottedKeys): static
    {
        $this->setOption('hideKeyValues', json_encode($dottedKeys));

        return $this;
    }

    /**
     * Json format validation. Accepts a list of keys or nested.keys and their validation rules.
     *
     * @return $this
     */
    public function jsonFormatValidation(array $rules): static
    {
        $this->inlineValidation(fn ($value) => WRLAHelper::jsonFormatValidation($value, $rules));

        return $this;
    }

    /**
     * Calculated value
     */
    public function calculatedValue(mixed $value): string
    {
        // If value is empty string we are likely creating a new record, so we set it to value json so
        // that we can apply default key values
        if ($value === '') {
            $value = '{}';
        }

        // If $value is array, convert to json first
        if (is_array($value)) {
            $value = json_encode($value);
        }

        // Check if json is valid, if not then do not format it and show as is
        $jsonData = json_decode((string) $value, true) ?? false;

        if ($jsonData !== false) {
            // Set default values recursilvely if they are not set
            $recursive = function ($jsonData, $defaultKeyValues) use (&$recursive) {
                foreach ($defaultKeyValues as $key => $value) {
                    if (is_array($value)) {
                        if (! isset($jsonData[$key])) {
                            $jsonData[$key] = [];
                        }
                        $jsonData[$key] = $recursive($jsonData[$key], $value);
                    } else {
                        // Get type of default value
                        $type = gettype($value);

                        // If not set at all, set it
                        if (!isset($jsonData[$key])) {
                            $jsonData[$key] = $value;
                        }
                        // Otherwise we can adjust the type to use the same type as the default value
                        elseif ($type === 'string' && !is_string($jsonData[$key])) {
                            $jsonData[$key] = (string) $jsonData[$key];
                        } elseif ($type === 'integer' && !is_int($jsonData[$key])) {
                            $jsonData[$key] = (int) $jsonData[$key];
                        } elseif ($type === 'double' && !is_double($jsonData[$key])) {
                            $jsonData[$key] = (float) $jsonData[$key];
                        } elseif ($type === 'float' && !is_float($jsonData[$key])) {
                            $jsonData[$key] = (float) $jsonData[$key];                        
                        } elseif ($type === 'boolean' && !is_bool($jsonData[$key])) {
                            $jsonData[$key] = (bool) $jsonData[$key];
                        }
                    }
                }

                return $jsonData;
            };

            // Merge default key values with json data
            $jsonData = $recursive($jsonData, $this->defaultKeyValues);
        }

        $value = json_encode($jsonData);

        // If value is just an empty array '[]', we instead set it to an empty object '{}'
        if ($value === '[]') {
            $value = '{}';
        }

        return trim($value);
    }

    /**
     * Render the input field.
     */
    public function render(): mixed
    {
        // Get name
        $name = $this->getName();

        // Check if value is set in old() and set if so
        if (old($name) !== null) {
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
