<?php

namespace WebRegulate\LaravelAdministration\Classes\InstanceActions;

use WebRegulate\LaravelAdministration\Classes\InstanceAction;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions;

class InstanceActionDelete
{
    public static function make(ManageableModel $manageableModel, bool $permanent = false, ?string $modelUrlAlias = null, int|null $modelId = null): InstanceAction
    {
        $modelUrlAlias ??= $manageableModel::getUrlAlias();
        $modelId ??= $manageableModel->model()->id ?? null;

        $confirmMessage = $permanent
            ? 'Are you sure you want to permanently delete this item?'
            : 'Are you sure?';

        $attributes = [
            'x-on:click' => <<<JS
                confirm('$confirmMessage')
                    ? \$dispatch('deleteModel', { 'modelUrlAlias': '$modelUrlAlias', 'id': $modelId })
                    : event.stopImmediatePropagation();
            JS,
        ];

        if ($permanent) {
            $attributes['title'] = 'Permanent delete';
        }

        return InstanceAction::make($manageableModel, 'Delete', 'fa fa-trash', 'danger')
            ->enableOnCondition($manageableModel::getPermission(ManageableModelPermissions::DELETE))
            ->setAdditionalAttributes($attributes);
    }
}
