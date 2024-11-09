<?php

namespace WebRegulate\LaravelAdministration\Enums;

enum PageType: string
{
    case CREATE = 'CREATE';
    case EDIT = 'EDIT';
    case BROWSE = 'BROWSE';
    case GENERAL = 'GENERAL';
}
