<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

class BrowseColumnCheck extends BrowseColumnIcon
{
    /**
     * Icon class string to display when the column value is truthy
     */
    protected string $trueIcon = 'fa-solid fa-circle-check text-emerald-500';

    /**
     * Icon class string to display when the column value is falsy
     */
    protected string $falseIcon = 'fa-regular fa-circle-xmark text-slate-500';

    /**
     * Create a new instance of the class
     *
     * @param  ?string  $label  Label of the column, defaults to title version of the column name
     */
    public static function make(?string $label, ?callable $iconBuilderCallback = null): static
    {
        // Create new instance
        $browseColumnIconCheck = new static($label);

        // Apply shared icon column defaults, but allow ordering
        $browseColumnIconCheck->applyIconDefaults();
        $browseColumnIconCheck->allowOrdering(true);

        // Set the override render value callback which picks the icon based on the truthiness of the value
        $browseColumnIconCheck->overrideRenderValue = function ($value, $model) use ($browseColumnIconCheck) {
            return $browseColumnIconCheck->renderIcon(
                $value ? $browseColumnIconCheck->trueIcon : $browseColumnIconCheck->falseIcon
            );
        };

        return $browseColumnIconCheck;
    }

    /**
     * Override the icon class string displayed when the column value is truthy
     */
    public function trueIcon(string $iconClass): static
    {
        $this->trueIcon = $iconClass;

        return $this;
    }

    /**
     * Override the icon class string displayed when the column value is falsy
     */
    public function falseIcon(string $iconClass): static
    {
        $this->falseIcon = $iconClass;

        return $this;
    }
}
