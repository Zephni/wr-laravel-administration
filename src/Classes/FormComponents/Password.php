<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Class Input
 *
 * This class is responsible for generating input fields.
 */
class Password extends FormComponent
{
    /**
     * Apply value. May be overriden in special cases, such as when applying a hash to a password.
     *
     * @param mixed $value
     * @return mixed
     */
    public function applyValue(mixed $value): mixed
    {
        // First hash the password
        $value = Hash::make($value);

        return $value;
    }

    /**
     * Render the input field.
     *
     * @param mixed $inject
     * @return mixed
     */
    public function render($inject = null): mixed
    {
        // Check if wrla_show_name is set
        $wrla_show = old('wrla_show_' . $this->attributes['name']) == '1' ? 'true' : 'false';

        // Contain password and checkbox within a parent div
        $HTML = <<<HTML
            <div x-data="{ userWantsToChange: $wrla_show }" class="w-full">
        HTML;

        $HTML .= view(WRLAHelper::getViewPath('components.forms.label'), [
            'for' => $this->attributes['name'],
            'label' => Str::title(str_replace('_', ' ', $this->attributes['name'])),
            'attr' => [
                'class' => 'mb-2'
            ],
        ])->render();

        // Checkbox to show/enable password field
        $HTML .= view(WRLAHelper::getViewPath('components.forms.input-checkbox'), [
            'name' => 'wrla_show_' . $this->attributes['name'],
            'label' => 'Change ' . Str::title(str_replace('_', ' ', $this->attributes['name'])),
            'value' => $wrla_show == 'true',
            'attr' => collect($this->attributes)
                        ->forget(['name', 'value', 'type'])
                        // If checked, flip userWantsToChange
                        ->merge(['@click' => '
                            userWantsToChange = !userWantsToChange;
                            if (userWantsToChange) {
                                $nextTick(() => { $refs.passwordField.focus(); });
                            }'])
                        ->toArray(),
        ])->render();

        // Render password field (hide if checkbox not checked)
        $HTML .= parent::render(view(WRLAHelper::getViewPath('components.forms.input-text'), [
            'name' => $this->attributes['name'],
            'value' => '',
            'type' => 'password',
            'attr' => collect($this->attributes)
                        ->forget(['name', 'value', 'type'])
                        // Set show and set class hidden if userWantsToChange is false
                        ->merge([
                            'wire:model' => 'formFields.' . $this->attributes['name'],
                            'x-ref' => 'passwordField',
                            'x-show' => 'userWantsToChange',
                            'x-bind:disabled' => '!userWantsToChange',
                        ])
                        ->toArray(),
        ])->render());

        // Close parent div
        $HTML .= <<<HTML
            </div>
        HTML;

        return $HTML;
    }
}
