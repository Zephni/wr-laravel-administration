<?php

namespace WebRegulate\LaravelAdministration\Classes\InstanceActions;

use WebRegulate\LaravelAdministration\Classes\InstanceAction;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
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

        $multiConfirmMessage = $permanent
            ? 'Are you sure you want to permanently delete the selected items?'
            : 'Are you sure you want to delete the selected items?';

        return InstanceAction::make($manageableModel, 'Delete', 'fa fa-trash', 'danger')
            ->requireCondition($manageableModel::getPermission(ManageableModelPermissions::DELETE))
            ->setAdditionalAttributes($attributes)
            ->multiAction(function (array $ids) use ($manageableModel) {
                // Check has delete permission
                if (! $manageableModel::getPermission(ManageableModelPermissions::DELETE)) {
                    return 'You do not have permission to delete these items.';
                }

                $deleted = 0;
                $failed = 0;

                foreach ($ids as $id) {
                    [$success] = WRLAHelper::deleteModel($manageableModel, (int) $id);
                    $success ? $deleted++ : $failed++;
                }

                $message = $deleted.' '.str($manageableModel::getDisplayName())->plural($deleted)->toString().' deleted.';

                if ($failed > 0) {
                    $message .= ' '.$failed.' could not be deleted.';
                }

                return $message;
            }, $multiConfirmMessage);
    }
}
