<?php

namespace WebRegulate\LaravelAdministration\Enums;

enum UpsertType: string
{
    case CREATE = 'CREATE';
    case EDIT = 'EDIT';

    public function getString(): string
    {
        return match ($this) {
            UpsertType::CREATE => 'Create',
            UpsertType::EDIT => 'Edit',
        };
    }
}
