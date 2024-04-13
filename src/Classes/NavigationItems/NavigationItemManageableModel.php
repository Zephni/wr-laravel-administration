<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

class NavigationItemManageableModel extends NavigationItem
{
    public function __construct(
        public string $modelClass,
    ) {
        // Check that $modelClass extends ManageableModel
        if(!is_subclass_of($modelClass, 'WebRegulate\LaravelAdministration\Classes\ManageableModel')) {
            throw new \Exception("Model class `$modelClass` must extend ManageableModel when passing to navigation item.");
        }

        parent::__construct(
            'wrla.browse',
            ['modelUrlAlias' => $modelClass::getUrlAlias()],
            $modelClass::getDisplayName(),
            $modelClass::getIcon(),
            [
                new NavigationItem(
                    'wrla.browse',
                    ['modelUrlAlias' => $modelClass::getUrlAlias()],
                    'Browse',
                    'fa fa-list'
                ),
                new NavigationItem(
                    'wrla.create',
                    ['modelUrlAlias' => $modelClass::getUrlAlias()],
                    'Create',
                    'fa fa-plus'
                ),
            ]
        );
    }
}
