<?php

namespace WebRegulate\LaravelAdministration\Classes\FormComponents;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use WebRegulate\LaravelAdministration\Enums\PageType;
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
     * @param PageType $upsertType
     * @return mixed
     */
    public function render(PageType $upsertType): mixed
    {
        $HTML = '';

        if($upsertType == PageType::EDIT) {
            // Check if wrla_show_name is set
            $wrla_show = old('wrla_show_' . $this->attributes['name']) == '1' ? 'true' : 'false';

            // Contain password and checkbox within a parent div
            $HTML .= <<<HTML
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
        }

        // Render password field (hide if checkbox not checked)
        $HTML .= view(WRLAHelper::getViewPath('components.forms.input-text'), [
            'name' => $this->attributes['name'],
            'label' => $upsertType == PageType::EDIT
                ? null
                : str(str_replace('_', ' ', $this->attributes['name']))->title(),
            'value' => '',
            'type' => 'password',
            'attr' => collect($this->attributes)
                        ->forget(['name', 'value', 'type'])
                        // Set show and set class hidden if userWantsToChange is false
                        ->merge($upsertType == PageType::EDIT ? [
                            'x-ref' => 'passwordField',
                            'x-show' => 'userWantsToChange',
                            'x-bind:disabled' => '!userWantsToChange',
                        ] : [])
                        ->toArray(),
        ])->render();

        // Render confirm password field (hide if checkbox not checked)
        $HTML .= view(WRLAHelper::getViewPath('components.forms.input-text'), [
            'name' => $this->attributes['name'].'_confirmation',
            'value' => '',
            'type' => 'password',
            'attr' => collect($upsertType == PageType::EDIT ? [
                        'x-show' => 'userWantsToChange',
                        'x-bind:disabled' => '!userWantsToChange',
                    ] : [])->merge([
                        'placeholder' => 'Confirm ' . str(str_replace('_', ' ', $this->attributes['name']))->title(),
                    ])
                    ->toArray(),
        ])->render();

        if($upsertType == PageType::EDIT) {
            // Close parent div
            $HTML .= <<<HTML
                </div>
            HTML;
        }

        return $HTML;
    }
}