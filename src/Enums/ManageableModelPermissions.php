<?php

namespace WebRegulate\LaravelAdministration\Enums;

enum ManageableModelPermissions: string
{
    case CREATE = 'CREATE';
    case BROWSE = 'BROWSE';
    case EDIT = 'EDIT';
    case DELETE = 'DELETE';
    case RESTORE = 'RESTORE';

    public function getString(): string
    {
        return match ($this) {
            self::CREATE => 'CREATE',
            self::BROWSE => 'BROWSE',
            self::EDIT => 'EDIT',
            self::DELETE => 'DELETE',
            self::RESTORE => 'RESTORE',
        };
    }
}
