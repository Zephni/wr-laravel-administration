<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Http\Request;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLARedirectException;

/**
 * Class Input
 *
 * This class is responsible for generating input fields.
 */
class Json extends ManageableField
{
    /**
     * Option to hide containing braces.
     */
    const OPTION_HIDE_CONTAINING_BRACES = 'HIDE_CONTAINING_BRACES';

    /**
     * Default key values to merge with nested.key values.
     * 
     * @var array
     */
    protected array $defaultKeyValues = [];

    /**
     * Post constructed method, called after name and value attributes are set.
     *
     * @return $this
     */
    public function postConstructed(): self
    {
        $this->validation('json');

        return $this;
    }

    /**
     * Pre validation method, called before validation rules are set.
     * 
     * @param ?string $value
     * @return bool Return true if we have changed the value and want to force merge into request input
     */
    public function preValidation(?string $value): bool
    {
        if(!$this->option(self::OPTION_HIDE_CONTAINING_BRACES)) {
            return false;
        }

        // Trim, if null, or starts or ends with curly or square braces, handle as normal
        $value = trim($value);
        if(
            $value == 'null' ||
            (str_starts_with($value, '{') && str_ends_with($value, '}')) ||
            (str_starts_with($value, '[') && str_ends_with($value, ']'))
        ) {
            return false;
        }

        // First try with curly braces
        $correctedValue = '{' . $value . '}';
        $jsonDecoded = json_decode($correctedValue, true);
        $correctedValue = json_encode($jsonDecoded, JSON_UNESCAPED_SLASHES);

        // If not valid json, try with square braces
        if($correctedValue === 'null') {
            $correctedValue = '[' . $value . ']';
            $jsonDecoded = json_decode($correctedValue);
            $correctedValue = json_encode($jsonDecoded, JSON_UNESCAPED_SLASHES);
        }

        // If still not valid json, return false and let the validator handle it
        if($correctedValue === 'null') {
            return false;
        }

        // Set value
        $this->attribute('value', $correctedValue);

        return true;
    }

    /**
     * Merge default keys and nested.key values before render.
     * 
     * @param array $defaultKeyValues
     * @return $this
     */
    public function mergeDefaultKeyValues(array $defaultKeyValues): self
    {
        $this->defaultKeyValues = $defaultKeyValues;

        return $this;
    }

    /**
     * Hide containing braces.
     * 
     * @param bool $hide
     * @return $this
     */
    public function hideContainingBraces(bool $hide = true): self
    {
        $this->option(self::OPTION_HIDE_CONTAINING_BRACES, $hide);

        return $this;
    }

    /**
     * Json format validation. Accepts a list of keys or nested.keys and their validation rules.
     * 
     * @param array $rules
     * @return $this
     */
    public function jsonFormatValidation(array $rules): self
    {
        $this->inlineValidation(function($value) use ($rules) {
            return WRLAHelper::jsonFormatValidation($value, $rules);
        });

        return $this;
    }

    /**
     * Apply submitted value. May be overriden in special cases, such as when applying a hash to a password.
     *
     * @param Request $request
     * @param mixed $value
     * @return mixed
     */
    public function applySubmittedValue(Request $request, mixed $value): mixed
    {
        // Convert json from non pretty print to plain minimalistic json
        try {
            // Check if valid json
            if(json_last_error() !== JSON_ERROR_NONE) {
                throw new WRLARedirectException(
                    'Invalid JSON format in ' . $this->getLabel() . ' field.'
                );
            }
        } catch (WRLARedirectException $e) {
            $e->redirect();
        }

        return $value;
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
            $value = '[]';
        }

        // Check if json is valid, if not then do not format it and show as is
        $jsonData = json_decode($value) !== null
            ? json_decode($value)
            : false;

        if($jsonData !== false) {
            // Now we have validation, we can loop through the merge default key values and apply them
            foreach($this->defaultKeyValues as $key => $value) {
                if(data_get($jsonData, $key) === null) {
                    data_set($jsonData, $key, $value);
                }
            }

            // If not empty, pretty print json
            $value = !empty($jsonData)
                ? json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : '{}';
        }

        // Trim whitespace from the value
        $value = trim($value);

        // If hide braces option set, we need to do a final check to remove any outer braces
        if($this->option(self::OPTION_HIDE_CONTAINING_BRACES)) {
            // If value has outer braces, remove them
            if(
                (str_starts_with($value, '{') && str_ends_with($value, '}')) ||
                (str_starts_with($value, '[') && str_ends_with($value, ']'))
            ) {
                $value = substr($value, 1, -1);
            }

            // Remove first tier of leading whitespace
            $value = str_replace("\n    ", "\n", $value);
        }

        return $value;
    }

    /**
     * Render the input field.
     *
     * @param PageType $upsertType
     * @return mixed
     */
    public function render(PageType $upsertType): mixed
    {
        // Check if value is set in old() and set if so
        if(old($this->attributes['name']) !== null) {
            $value = old($this->attributes['name']);
            $this->attribute('value', $value);
        } else {
            $value = $this->attributes['value'];
        }

        // Apply calculated value, which will apply default key values and pretty print json
        $value = $this->calculatedValue($value);

        return view(WRLAHelper::getViewPath('components.forms.textarea'), [
            'ignoreOld' => true,
            'name' => $this->attributes['name'],
            'label' => $this->getLabel(),
            'value' => $value,
            'attr' => collect($this->attributes)
                ->forget(['name', 'value'])
                ->toArray(),
        ])->render();
    }
}
