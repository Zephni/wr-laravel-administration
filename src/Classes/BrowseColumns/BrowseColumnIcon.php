<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

class BrowseColumnIcon extends BrowseColumnBase
{
    /**
     * Icon builder callable, takes $model and must return an icon class string (eg. 'fa-solid fa-circle-check text-emerald-500').
     *
     * @var callable
     */
    protected $iconBuilderCallback;

    /**
     * Create a new instance of the class
     *
     * @param  ?string  $label  Label of the column, defaults to title version of the column name
     * @param  callable  $iconBuilderCallback  Takes $model and must return an icon class string (eg. 'fa-solid fa-circle-check text-emerald-500')
     */
    public static function make(?string $label, callable $iconBuilderCallback): static
    {
        // Create new instance
        $browseColumnIcon = new static($label);

        // Set the icon builder callback
        $browseColumnIcon->iconBuilderCallback = $iconBuilderCallback;

        // Apply shared icon column defaults
        $browseColumnIcon->applyIconDefaults();

        // Set the override render value callback which internally calls the icon builder callback
        $browseColumnIcon->overrideRenderValue = function ($value, $model) use ($browseColumnIcon) {
            return $browseColumnIcon->renderIcon(
                call_user_func($browseColumnIcon->iconBuilderCallback, $model)
            );
        };

        return $browseColumnIcon;
    }

    /**
     * Apply default options shared by all icon-based browse columns.
     * Called during make() so subclasses can reuse without duplicating logic.
     */
    protected function applyIconDefaults(): void
    {
        $this->allowOrdering(false)
            ->renderHtml(true)
            ->headerClass('justify-center')
            ->columnClass('justify-center');
    }

    /**
     * Render the given icon class string
     */
    protected function renderIcon(?string $iconClass): string
    {
        if (empty($iconClass)) {
            return '';
        }

        $iconClass = e($iconClass);

        return <<<HTML
            <i class="$iconClass"></i>
        HTML;
    }
}
