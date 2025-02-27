<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class BrowseColumnImage extends BrowseColumnBase
{
    /**
     * Create a new instance of the class
     *
     * @param string|null $label
     * @return static
     */
    public static function make(?string $label, string $imagePath, null|string|int $width = 140): static
    {
        // Build base browse column image
        $browseColumnImage = (new self($label))
            ->allowOrdering(false)
            ->renderHtml(true)
            ->setOptions([
                'width' => $width,
                'value' => $imagePath
            ]);

        // Set override render value callback
        $browseColumnImage->overrideRenderValue = function ($value, $model) use ($browseColumnImage) {
            $renderedView = view(WRLAHelper::getViewPath('components.forced-aspect-image', false), [
                "src" => $value,
                "class" => $browseColumnImage->getOption('class') ?? ' border border-slate-400',
                "aspect" => $browseColumnImage->getOption('aspect')
            ])->render();

            return <<<BLADE
                <a href="$value" target="_blank">$renderedView</a>
            BLADE;
        };

        return $browseColumnImage;
    }
}
