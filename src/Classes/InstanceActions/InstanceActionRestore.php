<?php

namespace WebRegulate\LaravelAdministration\Classes\InstanceActions;

use WebRegulate\LaravelAdministration\Classes\InstanceAction;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Enums\ManageableModelPermissions;

class InstanceActionRestore
{
    public static function make(ManageableModel $manageableModel): InstanceAction
    {
        return InstanceAction::make($manageableModel, 'Restore', 'fa fa-undo', 'primary')
            ->requireCondition(
                $manageableModel::getPermission(ManageableModelPermissions::RESTORE)
                && $manageableModel->isModelSoftDeleted()
            )
            ->setAdditionalAttributes([
                'wire:click' => 'restoreModel(' . $manageableModel->model()->id . ')',
            ]);
    }
}
