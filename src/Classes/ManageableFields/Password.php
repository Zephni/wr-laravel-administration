<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Traits\ManageableField;

class Password
{
    use ManageableField;

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
     */
    public function applySubmittedValue(Request $request, mixed $value): mixed
    {
        // Hash password by user data hashPassword method if available
        if(method_exists(WRLAHelper::getUserDataModelClass(), 'hashPassword')) {
            return WRLAHelper::getUserDataModelClass()::hashPassword($value);
        }
        
        return Hash::make($value);
    }

    /**
     * Render the input field.
     */
    public function render(): mixed
    {
        $HTML = '';

        $pageType = WRLAHelper::getCurrentPageType();

        if ($pageType == PageType::EDIT) {
            // Check if wrla_show_name is set
            $wrla_show = old('wrla_show_'.$this->getAttribute('name')) == '1' ? 'true' : 'false';

            // Contain password and checkbox within a parent div
            $HTML .= <<<HTML
                <div x-data="{ userWantsToChange: $wrla_show }" class="w-full">
            HTML;

            $HTML .= view(WRLAHelper::getViewPath('components.forms.label'), [
                'label' => $this->getLabel(),
                'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                    'for' => $this->getAttribute('name'),
                    'class' => 'mb-2',
                ])),
            ])->render();

            // Flex container
            $HTML .= '<div class="flex flex-col gap-2">';

            // Checkbox to show/enable password field
            $HTML .= view(WRLAHelper::getViewPath('components.forms.input-checkbox'), [
                'label' => 'Change '.Str::title(str_replace('_', ' ', $this->getLabel())),
                'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                    'name' => 'wrla_show_'.$this->getAttribute('name'),
                    'value' => $wrla_show == 'true',
                    '@click' => 'userWantsToChange = !userWantsToChange;
                        if (userWantsToChange) {
                            $nextTick(() => { $refs.passwordField.focus(); });
                        }',
                ])),
            ])->render();
        }

        // Render password field (hide if checkbox not checked)
        $HTML .= view(WRLAHelper::getViewPath('components.forms.input-text'), [
            'label' => $pageType == PageType::EDIT ? null : $this->getLabel(),
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => '',
                'type' => 'password',
            ], $pageType == PageType::EDIT ? [
                'x-ref' => 'passwordField',
                'x-show' => 'userWantsToChange',
                'x-bind:disabled' => '!userWantsToChange',
            ] : [])),
        ])->render();

        // Render confirm password field (hide if checkbox not checked)
        $HTML .= view(WRLAHelper::getViewPath('components.forms.input-text'), [
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name').'_confirmation',
                'value' => '',
                'type' => 'password',
                'placeholder' => 'Confirm '.strtolower((string) $this->getLabel()),
            ], $pageType == PageType::EDIT ? [
                'x-show' => 'userWantsToChange',
                'x-bind:disabled' => '!userWantsToChange',
            ] : [])),
        ])->render();

        if ($pageType == PageType::EDIT) {
            // Close parent div
            $HTML .= <<<'HTML'
                    </div>
                </div>
            HTML;
        }

        return $HTML;
    }
}
