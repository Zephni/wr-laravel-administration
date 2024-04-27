<?php

namespace WebRegulate\LaravelAdministration\Enums;

enum UpsertType: string
{
    case CREATE = 'CREATE';
    case EDIT = 'EDIT';
}
