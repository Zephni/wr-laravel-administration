<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class BrowseColumn extends BrowseColumnBase
{
    /**
     * Create a new instance of the class
     *
     * @param string|null $label
     * @return static
     */
    public static function make(?string $label): static
    {
        return new self($label);
    }
}
