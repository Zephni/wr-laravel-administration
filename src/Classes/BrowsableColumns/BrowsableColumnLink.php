<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowsableColumns;

use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class BrowsableColumnLink extends BrowsableColumnBase
{
    /**
     * Link builder callable, must take $value, $model as arguments and return an array of
     * [
     *     'url'   => string,
     *     'label' => string,
     *     'icon'  => ?string // optional,
     *     'class' => ?string // optional
     * ]
     *
     * @var callable
     */
    protected $linkBuilderCallback;

    /**
     * Create a new instance of the class
     *
     * @param string|null $label
     * @param callable $linkBuilderCallback Must take $value, $model as arguments and return an array of ['url' => string, 'label' => string, 'icon' => ?string]
     * @param null|integer|string|null $width
     * @return static
     */
    public static function make(?string $label, callable $linkBuilderCallback, null|int|string $width = null): static
    {
        // Create new instance
        $browsableColumnLink = new static($label, 'link', $width);

        // Set the link builder callback
        $browsableColumnLink->linkBuilderCallback = $linkBuilderCallback;

        // Set the override render value callback which internally calls the link builder callback
        $browsableColumnLink->overrideRenderValue = function ($value, $model) use ($browsableColumnLink) {
            $linkData = call_user_func($browsableColumnLink->linkBuilderCallback, $value, $model);
            return view(WRLAHelper::getViewPath('components.forms.link'), [
                'href' => $linkData['url'],
                'text' => $linkData['text'],
                'icon' => $linkData['icon'] ?? null,
                'attributes' => new ComponentAttributeBag([
                    'class' => $linkData['class'] ?? 'font-semibold',
                ]),
            ]);
        };

        return $browsableColumnLink;
    }
}
