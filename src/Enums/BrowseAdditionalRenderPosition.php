<?php

namespace WebRegulate\LaravelAdministration\Enums;

enum BrowseAdditionalRenderPosition: string
{
    case TOP = 'top';
    case BELOW_HEADING = 'below_heading';
    case BELOW_BROWSE_ACTIONS = 'below_browse_actions';
    case BELOW_FILTERS = 'below_filters';
}
