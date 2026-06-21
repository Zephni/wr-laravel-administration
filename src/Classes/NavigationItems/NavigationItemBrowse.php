<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

class NavigationItemBrowse
{
    /**
     * Make a "Browse" child navigation item for the given manageable model class.
     */
    public static function make(string $manageableModelClass, string $name = 'Browse', string $icon = 'fa fa-list'): NavigationItem
    {
        return NavigationItem::make(
            url: route('wrla.manageable-models.browse', ['modelUrlAlias' => $manageableModelClass::getUrlAlias()]),
            name: $name,
            icon: $icon,
        );
    }
}
