<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\ComponentAttributeBag;
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
        $this->setAttribute('placeholder', 'Atleast 6 characters, and have atleast 1 uppercase, 1 lowercase, 1 number');
        $this->setOption('ignoreOld', true);

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
                'label' => $this->getLabel(),
                'attributes' => new ComponentAttributeBag(array_merge($this->attributes, [
                    'for' => $this->attributes['name'],
                    'class' => 'mb-2'
                ])),
            ])->render();

            // Checkbox to show/enable password field
            $HTML .= view(WRLAHelper::getViewPath('components.forms.input-checkbox'), [
                'label' => 'Change ' . Str::title(str_replace('_', ' ', $this->attributes['name'])),
                'attributes' => new ComponentAttributeBag(array_merge($this->attributes, [
                    'name' => 'wrla_show_' . $this->attributes['name'],
                    'value' => $wrla_show == 'true',
                    '@click' => 'userWantsToChange = !userWantsToChange;
                        if (userWantsToChange) {
                            $nextTick(() => { $refs.passwordField.focus(); });
                        }'
                ])),
            ])->render();
        }

        // Render password field (hide if checkbox not checked)
        $HTML .= view(WRLAHelper::getViewPath('components.forms.input-text'), [
            'label' => $upsertType == PageType::EDIT ? null : $this->getLabel(),
            'attributes' => new ComponentAttributeBag(array_merge($this->attributes, [
                'name' => $this->attributes['name'],
                'value' => '',
                'type' => 'password',
            ], $upsertType == PageType::EDIT ? [
                'x-ref' => 'passwordField',
                'x-show' => 'userWantsToChange',
                'x-bind:disabled' => '!userWantsToChange',
            ] : []))
        ])->render();

        // Render confirm password field (hide if checkbox not checked)
        $HTML .= view(WRLAHelper::getViewPath('components.forms.input-text'), [
            'attributes' => new ComponentAttributeBag(array_merge($this->attributes, [
                'name' => $this->attributes['name'].'_confirmation',
                'value' => '',
                'type' => 'password',
                'placeholder' => 'Confirm ' . str(str_replace('_', ' ', $this->attributes['name']))->title(),
            ], $upsertType == PageType::EDIT ? [
                'x-show' => 'userWantsToChange',
                'x-bind:disabled' => '!userWantsToChange',
            ] : [])),
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
