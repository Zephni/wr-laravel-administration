<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Support\Arr;
use function PHPSTORM_META\type;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

use WebRegulate\LaravelAdministration\Traits\ManageableField;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class JsonUI
{
    use ManageableField;

    /**
     * Levels nested
     * @var int
     */
    private int $levelsNested = 0;

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param ?ManageableModel $manageableModel
     * @param ?mixed $column
     * @param ?array $fieldSettings
     * @param ?array $options bool allowCreate (true)
     * @return static
     */
    public static function make(?ManageableModel $manageableModel, ?string $column, ?array $fieldSettings = null, ?array $options = null): static
    {
        $manageableField = new static($column, $manageableModel?->getModelInstance()->{$column}, $manageableModel);

        $options['fieldSettings'] = $fieldSettings;

        if(!is_null($options)) {
            $manageableField->setOptions(array_merge([
                'allowCreate' => true,
            ],$options));
        }

        return $manageableField;
    }

    /**
     * Render the input field.
     *
     * @return mixed
     */
    public function render(): mixed
    {
        // Get decoded JSON data
        $jsonData = json_decode($this->getValue(), true);

        // Render view
        return view(WRLAHelper::getViewPath('components.forms.json-ui'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'jsonData' => $jsonData,
            'attributes' => Arr::toAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => $this->getValue(),
                'type' => $this->getAttribute('type') ?? 'text',
            ])),

        ])->render();
    }
}
