<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Traits\ManageableField;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

use function PHPSTORM_META\type;

class JsonUI
{
    use ManageableField;

    /**
     * Blade code, built from column data / field settings / options on render.
     * @var string
     */
    private string $bladeCode = '';

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

        // Build blade code for JSON UI
        $this->buildBladeCodeFromJsonData($jsonData);

        // Render view
        return view(WRLAHelper::getViewPath('components.forms.json-ui'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'bladeCode' => $this->bladeCode,
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => $this->getValue(),
                'type' => $this->getAttribute('type') ?? 'text',
            ])),

        ])->render();
    }

    /**
     * Build the blade code for the JSON UI. Calls self recursively to build the nested structure.
     */
    public function buildBladeCodeFromJsonData(array $jsonData): void
    {
        // Start group
        $this->startGroup();

        // Loop through each key-value pair in the JSON data
        foreach ($jsonData as $key => $value)
        {
            // If value is array
            if (is_array($value))
            { 
                // If int indexed array, open horizontal group
                if($this->levelsNested != 1 && is_int($key)) $this->bladeCode .= '<div class="flex flex-row items-start gap-0">';

                // Display label
                if($this->levelsNested == 1 && is_int($key)) {
                    $this->displayLabel("item #$key", '!font-bold bg-slate-200 rounded-t-md px-3 py-1 border-b border-slate-400');
                }
                else if($this->levelsNested == 1) {
                    $this->displayLabel($key, '!font-bold bg-slate-200 rounded-t-md px-3 py-1 border-b border-slate-400');
                }
                else if(is_int($key)) {
                    $this->displayLabel("item #$key", 'relative top-[2px]');
                }
                else {
                    $this->displayLabel($key, '!font-bold bg-slate-100 rounded-t-md px-3 py-1 border-b border-slate-400');
                }

                // Call rcursively to build the nested structure
                $this->buildBladeCodeFromJsonData($value);
                
                // If int indexed array, end horizontal group
                if($this->levelsNested != 1 && is_int($key)) $this->bladeCode .= '</div>';
            }
            // If is field
            else
            {
                // Start field group
                $this->bladeCode .= '<div class="flex flex-row gap-4 items-center">';

                // If it's not an array, display the label and value
                $this->displayLabel($key, '!font-bold');
                $this->bladeCode .= "<input type='text' class='w-72 px-2 py-0.5 border border-slate-400 rounded-md text-sm' name='{$this->getAttribute('name')}[$key]' value='$value'>";

                // End field group
                $this->bladeCode .= '</div>';
            }
        }

        // End group
        $this->endGroup();
    }

    /**
     * Start group
     */
    private function startGroup(): void {
        $this->bladeCode .= '<div class="flex flex-col gap-2 '.($this->levelsNested > 0 ? '!pl-5 ' : 'py-2 pr-2').' pl-2 bg-white rounded-md">';
        $this->levelsNested++;
    }

    /**
     * End group
     */
    private function endGroup(): void {
        $this->bladeCode .= '</div>';
        $this->levelsNested--;
    }

    /**
     * Display label
     */
    private function displayLabel(string $label, string $class = ''): void {
        $this->bladeCode .= "<label class='$class text-sm font-medium text-gray-700'>$label</label>";
    }
}
