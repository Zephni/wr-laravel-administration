<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

class NavigationItemDivider extends NavigationItem
{
    public function __construct(
        public string $class = 'w-10/12 border-t border-slate-550 mx-auto my-2'
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
