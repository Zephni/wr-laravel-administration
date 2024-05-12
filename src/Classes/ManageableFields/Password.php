<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class Password extends ManageableField
{
    /**
     * Post constructed method, called after name and value attributes are set.
     * 
     * @return $this
     */
    public function postConstructed(): static
    {
        $this->validation('required_if:wrla_show_password,1|string|confirmed|min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/');
        $this->attribute('placeholder', 'Atleast 6 characters, and have atleast 1 uppercase, 1 lowercase, 1 number');

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
                'label' => $this->getLabel(),
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
            'label' => $upsertType == PageType::EDIT ? null : $this->getLabel(),
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
