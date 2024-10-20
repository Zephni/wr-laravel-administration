<?php

namespace WebRegulate\LaravelAdministration\Enums;

enum PageType: string
{
    case CREATE = 'CREATE';
    case EDIT = 'EDIT';
    case BROWSE = 'BROWSE';
    case GENERAL = 'GENERAL';

    public function getString(): string
    {
        return match ($this) {
            PageType::CREATE => 'Create',
            PageType::EDIT => 'Edit',
            PageType::BROWSE => 'Browse',
            PageType::GENERAL => 'General',
        };
    }
}
