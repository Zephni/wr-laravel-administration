<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

use Illuminate\Support\Facades\Blade;

class NavigationItemHTML extends NavigationItem
{
    public function __construct(
        public string $html = '',
    ) {
        parent::__construct(
            'wrla::html',
            null,
            '',
            '',
            []
        );
    }

    /**
     * Overriden render method
     * 
     * @return string
     */
    public function render(): string
    {
        return Blade::render($this->html);
    }
}
