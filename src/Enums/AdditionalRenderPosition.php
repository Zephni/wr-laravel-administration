<?php

namespace WebRegulate\LaravelAdministration\Enums;

enum AdditionalRenderPosition: string
{
    // Browse page positions
    case BROWSE_TOP = 'browse_top';
    case BROWSE_BELOW_HEADING = 'browse_below_heading';
    case BROWSE_BELOW_ACTIONS = 'browse_below_actions';
    case BROWSE_BELOW_FILTERS = 'browse_below_filters';
}
