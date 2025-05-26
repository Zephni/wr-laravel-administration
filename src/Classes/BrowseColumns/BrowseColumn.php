<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

class BrowseColumn extends BrowseColumnBase
{
    /**
     * Create a new instance of the class
     * 
     * @param string|null $label Label of the column, defaults to title version of the column name
     * @param ?callable $overrideRenderValue Callback to override the render value of the column, receives the value and the model as arguments, must return a string value
     */
    public static function make(?string $label, ?callable $overrideRenderValue = null): static
    {
        $browseColumn = new self($label);

        if(!is_null($overrideRenderValue)) {
            $browseColumn->overrideRenderValue($overrideRenderValue);
        }

        return $browseColumn;
    }
}
