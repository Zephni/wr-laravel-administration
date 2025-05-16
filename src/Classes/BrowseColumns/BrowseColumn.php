<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

class BrowseColumn extends BrowseColumnBase
{
    /**
     * Create a new instance of the class
     */
    public static function make(?string $label): static
    {
        return new self($label);
    }
}
