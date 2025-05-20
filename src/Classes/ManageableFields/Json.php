<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Traits\ManageableField;

class Json
{
    use ManageableField;

    /**
     * Option to hide containing braces.
     */
    const OPTION_HIDE_CONTAINING_BRACES = 'HIDE_CONTAINING_BRACES';

    /**
     * Default key values to merge with nested.key values.
     */
    protected array $defaultKeyValues = [];

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
     * Pre validation method, called before validation rules are set.
     *
     * @return bool Return true if we have changed the value and want to force merge into request input
     */
    public function preValidation(?string $value): bool
    {
        if (! $this->getOption(static::OPTION_HIDE_CONTAINING_BRACES)) {
            return false;
        }

        // Trim, if null, or starts or ends with curly or square braces, handle as normal
        $value = trim((string) $value);
        if (
            $value == 'null' ||
            (str_starts_with($value, '{') && str_ends_with($value, '}')) ||
            (str_starts_with($value, '[') && str_ends_with($value, ']'))
        ) {
            return false;
        }

        // First try with curly braces
        $correctedValue = '{'.$value.'}';
        $jsonDecoded = json_decode($correctedValue, true);
        $correctedValue = json_encode($jsonDecoded, JSON_UNESCAPED_SLASHES);

        // If not valid json, try with square braces
        if ($correctedValue === 'null') {
            $correctedValue = '['.$value.']';
            $jsonDecoded = json_decode($correctedValue);
            $correctedValue = json_encode($jsonDecoded, JSON_UNESCAPED_SLASHES);
        }

        // If still not valid json, return false and let the validator handle it
        if ($correctedValue === 'null') {
            return false;
        }

        // Set value
        $this->setAttribute('value', $correctedValue);

        return true;
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
     * Hide containing braces.
     *
     * @return $this
     */
    public function hideContainingBraces(bool $hide = true): static
    {
        $this->setOption(static::OPTION_HIDE_CONTAINING_BRACES, $hide);

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
            $value = '[]';
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
                        if (! isset($jsonData[$key])) {
                            $jsonData[$key] = $value;
                        }
                    }
                }

                return $jsonData;
            };

            // Merge default key values with json data
            $jsonData = $recursive($jsonData, $this->defaultKeyValues);

            // If not empty, pretty print json
            $value = ! empty($jsonData)
                ? json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : '{}';
        }

        // Trim whitespace from the value
        $value = trim((string) $value);

        // If hide braces option set, we need to do a final check to remove any outer braces
        if ($this->getOption(static::OPTION_HIDE_CONTAINING_BRACES)) {
            // If value has outer braces, remove them
            if (
                (str_starts_with($value, '{') && str_ends_with($value, '}')) ||
                (str_starts_with($value, '[') && str_ends_with($value, ']'))
            ) {
                $value = substr($value, 1, -1);
            }

            // Remove first tier of leading whitespace
            $value = str_replace("\n    ", "\n", $value);
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

        // Apply calculated value, which will apply default key values and pretty print json
        $value = $this->calculatedValue($value);
        $this->setValue($value);

        return view(WRLAHelper::getViewPath('components.forms.textarea'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $name,
                'value' => $value,
            ])),
        ])->render();
    }
}
