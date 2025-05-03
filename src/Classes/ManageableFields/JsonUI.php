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
        $this->buildBladeCodeFromJsonData($jsonData, '!py-2');

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
    public function buildBladeCodeFromJsonData(array $jsonData, string $groupClass = ''): void
    {
        // Start group
        $this->startGroup($groupClass);

        // We prepend _wrla_key_ to the keys of the array (recursively) to fight against PHP's
        // ... auto array key casting for integers (loses correct indexing if not 0 based)
        $jsonData = Arr::prependKeysRecursive($jsonData, '_wrla_key_');

        // Sort the array so that non-array items come first
        $jsonData = array_merge(
            array_filter($jsonData, fn($value) => !is_array($value)),
            array_filter($jsonData, fn($value) => is_array($value))
        );

        // Loop through each key-value pair in the JSON data
        foreach ($jsonData as $key => $value)
        {
            // Remove the _wrla_key_ prefix from the key
            $key = ltrim($key, '_wrla_key_');

            // Check if can be parsed as int
            $keyIsInt = is_numeric($key) && (int)$key == $key;

            // If value is array
            if (is_array($value))
            {
                // If int indexed array, open horizontal group
                if($keyIsInt) $this->bladeCode .= '<div class="'.($key !== 0 ? 'mt-2' : '').' pb-2 flex flex-row items-start gap-0">';

                // Display label
                if($keyIsInt) {
                    $this->displayLabel("#$key", 'relative top-[6px]');
                }
                else {
                    $this->displayLabel($key . '<span class="!text-sm text-slate-300 ml-2">&#10148;</span>', 'mt-1.5 mb-1.5 !font-bold');
                }

                // Call rcursively to build the nested structure
                $this->buildBladeCodeFromJsonData($value, !$keyIsInt ? 'border-l border-b mb-0.5' : '');
                
                // If int indexed array, end horizontal group
                if($keyIsInt) $this->bladeCode .= '</div>';
            }
            // If is field
            else
            {
                // Start field group
                $this->bladeCode .= '<div class="flex flex-row gap-4 items-center py-1">';

                // If it's not an array, display the label and value
                $this->displayLabel($key, '!font-bold');
                $this->bladeCode .= "<input type='text' class='w-72 px-2 py-0.5 border border-slate-400 dark:text-black rounded-md text-sm' name='{$this->getAttribute('name')}[$key]' value='$value'>";

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
    private function startGroup(string $groupClass): void {
        $this->bladeCode .= '<div class="'.$groupClass.' flex flex-col mb-0 pl-5 py-0 rounded-b-md bg-white dark:bg-slate-600 border-slate-300">';
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
        $this->bladeCode .= "<label class='$class text-sm font-medium text-gray-700 dark:text-gray-200'>$label</label>";
    }
}
