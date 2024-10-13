<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class BrowseColumn extends BrowseColumnBase
{
    /**
     * Create a new instance of the class
     *
     * @param string|null $label
     * @param string $type
     * @param null|integer|string|null $width
     * @return static
     */
    public static function make(?string $label, string $type = 'string', null|int|string $width = null): static
    {
        return new self($label, $type, $width);
    }
}
