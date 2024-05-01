<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\WRLARedirectException;

/**
 * Class Input
 *
 * This class is responsible for generating input fields.
 */
class Json extends FormComponent
{
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
     * Apply value. May be overriden in special cases, such as when applying a hash to a password.
     *
     * @param mixed $value
     * @return mixed
     */
    public function applyValue(mixed $value): mixed
    {
        // Convert json from non pretty print to plain minimalistic json
        try {
            // Get decoded json
            $jsonDecoded = json_decode($value, true);

            // Check if valid json
            if(json_last_error() !== JSON_ERROR_NONE) {
                throw new WRLARedirectException(
                    'Invalid JSON format in ' . $this->getLabel() . ' field.'
                );
            }

            // Return minimized json
            return json_encode($jsonDecoded);
        } catch (WRLARedirectException $e) {
            $e->redirect();
            return $this->attribute('value');
        }
    }

    /**
     * Render the input field.
     *
     * @param PageType $upsertType
     * @return mixed
     */
    public function render(PageType $upsertType): mixed
    {
        return view(WRLAHelper::getViewPath('components.forms.textarea'), [
            'name' => $this->attributes['name'],
            'label' => $this->getLabel(),
            'value' => WRLAHelper::jsonPrettyPrint($this->attributes['value']),
            'attr' => collect($this->attributes)
                ->forget(['name', 'value'])
                ->toArray(),
        ])->render();
    }
}
