<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class BrowseColumnLink extends BrowseColumnBase
{
    /**
     * Link builder callable, must take $value, $model as arguments and return an array of
     * [
     *     'url'   => string,
     *     'label' => string,
     *     'icon'  => ?string // optional,
     *     'class' => ?string // optional
     * ]
     * or null if nothing should be displayed
     *
     * @var callable
     */
    protected $linkBuilderCallback;

    /**
     * Create a new instance of the class
     *
     * @param  callable  $linkBuilderCallback  Must take $value, $model as arguments and return an array of ['url' => string, 'label' => string, 'icon' => ?string, 'class' => ?string] or return null to display nothing
     */
    public static function make(?string $label, callable $linkBuilderCallback): static
    {
        // Create new instance
        $browseColumnLink = new static($label);

        // Set the link builder callback
        $browseColumnLink->linkBuilderCallback = $linkBuilderCallback;

        // Set the override render value callback which internally calls the link builder callback
        $browseColumnLink->overrideRenderValue = function ($value, $model) use ($browseColumnLink) {
            $linkData = call_user_func($browseColumnLink->linkBuilderCallback, $value, $model);

            if ($linkData === null) {
                return '';
            }

            return view(WRLAHelper::getViewPath('components.forms.link'), [
                'href' => $linkData['url'],
                'text' => $linkData['text'],
                'icon' => $linkData['icon'] ?? null,
                'attributes' => new ComponentAttributeBag([
                    'class' => $linkData['class'] ?? 'font-semibold',
                ]),
            ]);
        };

        return $browseColumnLink;
    }
}
