<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

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
        return $this->html;
    }
}
