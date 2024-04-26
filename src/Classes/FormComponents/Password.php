<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use Illuminate\Support\Str;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Class Input
 *
 * This class is responsible for generating input fields.
 */
class Password extends FormComponent
{
    /**
     * Render the input field.
     *
     * @param mixed $inject
     * @return mixed
     */
    public function render($inject = null): mixed
    {
        // Contain password and checkbox within a parent div
        $HTML = <<<HTML
            <div x-data="{ userWantsToChange: false }" class="w-full">
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
            'value' => false,
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
