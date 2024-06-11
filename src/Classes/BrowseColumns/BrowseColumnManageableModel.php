<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

use WebRegulate\LaravelAdministration\Classes\BrowseColumns\BrowseColumnLink;

class BrowseColumnManageableModel extends BrowseColumnBase
{

    /**
     * Create a new instance of the class
     *
     * @param string|null $label
     * @param string $relatedIdColumn
     * @param string $manageableModelClass
     * @param null|integer|string|null $width
     * @return BrowseColumnLink
     */
    public static function make(?string $label, string $relatedIdColumn, string $manageableModel, null|int|string $width = null): BrowseColumnLink
    {
        return BrowseColumnLink::make($label, function($value, $model) use($manageableModel, $relatedIdColumn) {
            if(empty($value)){
                return null;
            }
            
            return [
                'url' => route('wrla.manageable-models.edit', [
                    'modelUrlAlias' => $manageableModel::getUrlAlias(),
                    'id' => $model->{$relatedIdColumn},
                ]),
                'text' => $value,
                'icon' => 'fas fa-graduation-cap text-xs'
            ];
        }, $width);
    }
}
