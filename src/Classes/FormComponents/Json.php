<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLARedirectException;

/**
 * Class Input
 *
 * This class is responsible for generating input fields.
 */
class Json extends FormComponent
{
    /**
     * Option to hide containing braces.
     */
    const OPTION_HIDE_CONTAINING_BRACES = 'HIDE_CONTAINING_BRACES';

    /**
     * Hide containing braces.
     * 
     * @param bool $hide
     * @return $this
     */
    public function hideContainingBraces(bool $hide = true): static
    {
        $this->option(self::OPTION_HIDE_CONTAINING_BRACES, $hide);

        return $this;
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
        $correctedValue = json_encode($jsonDecoded);

        // If not valid json, try with square braces
        if($correctedValue === 'null') {
            $correctedValue = '[' . $value . ']';
            $jsonDecoded = json_decode($correctedValue);
            $correctedValue = json_encode($jsonDecoded);
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
     * Json format validation. Accepts a list of keys or nested.keys and their validation rules.
     * 
     * @param array $rules
     * @return $this
     */
    public function jsonFormatValidation(array $rules): static
    {
        $this->inlineValidation(function($value) use ($rules) {
            return WRLAHelper::jsonFormatValidation($value, $rules);
        });

        return $this;
    }

    /**
     * Apply value. May be overriden in special cases, such as when applying a hash to a password.
     *
     * @param mixed $value
     * @return mixed
     */
    public function applyValue(mixed $value): mixed
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
        }

        // Check if json is valid, if not then do not format it and show as is
        $validJson = json_decode($this->attributes['value']) !== null;

        if($validJson) {
            $value = !empty($this->attributes['value']) && $this->attributes['value'] != '[]'
                ? WRLAHelper::jsonPrettyPrint($this->attributes['value'])
                : '{}';
    
            // If hide braces option set, remove outer braces, and subtract 4 spaces from each line
            if($this->option(self::OPTION_HIDE_CONTAINING_BRACES)) {
                $value = trim($value);
                // If value has outer braces, remove them
                if(
                    (str_starts_with($value, '{') && str_ends_with($value, '}')) ||
                    (str_starts_with($value, '[') && str_ends_with($value, ']'))
                ) {
                    $value = substr($value, 1, -1);
                }
                $value = str_replace("\n    ", "\n", $value);
            }
        }

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
