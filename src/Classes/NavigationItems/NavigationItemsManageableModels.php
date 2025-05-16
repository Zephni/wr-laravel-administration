<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class NavigationItemsManageableModels extends NavigationItem
{
    /**
     * Import all manageable models as array of nav items
     */
    public static function import(array $manageableModelClasses = []): array
    {
        return self::getAManageableModelsNavigationItems($manageableModelClasses);
    }

    /**
     * Retrieves an array of navigation items for all manageable models.
     *
     * @return array An array of NavigationItemManageableModel objects representing the navigation items.
     */
    public static function getAManageableModelsNavigationItems(array $manageableModelClasses = []): array
    {
        $navItems = [];

        if (empty(ManageableModel::$manageableModels)) {
            return $navItems;
        }

        // If passed manageable model classes array is empty, get all manageable model classes
        if (empty($manageableModelClasses)) {
            $manageableModelClasses = array_keys(WRLAHelper::$globalManageableModelData);
        }

        foreach ($manageableModelClasses as $manageableModelClass) {
            $nimm = $manageableModelClass::getNavigationItem();

            if (WRLAHelper::$globalManageableModelData[ltrim((string) $manageableModelClass, '\\')]['hideFromNavigation']) {
                continue;
            }

            $navItems[] = $nimm;
        }

        return $navItems;
    }
}
