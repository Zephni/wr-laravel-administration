<?php

namespace WebRegulate\LaravelAdministration\Enums;

enum ManageableModelPermissions: string
{
    case ENABLED = 'ENABLED';
    case CREATE = 'CREATE';
    case BROWSE = 'BROWSE';
    case EDIT = 'EDIT';
    case DELETE = 'DELETE';
    case RESTORE = 'RESTORE';
}
