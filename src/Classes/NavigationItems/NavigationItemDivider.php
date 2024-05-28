<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

class NavigationItemDivider extends NavigationItem
{
    public function __construct(
        public string $class = 'w-10/12 border-t border-slate-500 mx-auto my-1'
    ) {
        parent::__construct(
            'wrla::divider',
            [],
            $class,
            '',
            []
        );
    }

    public function setClass(string $class): self
    {
        $this->name = $class;
        return $this;
    }

    public function appendClass(string $class): self
    {
        $this->name .= ' ' . $class;
        return $this;
    }
}
