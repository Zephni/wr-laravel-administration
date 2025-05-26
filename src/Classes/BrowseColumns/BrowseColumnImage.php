<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class BrowseColumnImage extends BrowseColumnBase
{
    /**
     * Create a new instance of the class
     *
     * @param string $imagePath Path to the image including filename. If callable is provided it takes the value as a parameter and must return an image path
     */
    public static function make(?string $label, string|callable $imagePath, null|string|int $width = 140): static
    {
        // Build base browse column image
        $browseColumnImage = (new self($label))
            ->allowOrdering(false)
            ->renderHtml(true)
            ->setOptions([
                'width' => $width,
                'value' => is_string($imagePath) ? $imagePath : null,
            ]);

        // Set override render value callback
        $browseColumnImage->overrideRenderValue = function ($value, $model) use ($browseColumnImage, $imagePath) {
            $value = is_string($imagePath) ? $imagePath : $imagePath($value, $model);

            $renderedView = view(WRLAHelper::getViewPath('components.forced-aspect-image', false), [
                'src' => $value,
                'class' => $browseColumnImage->getOption('class') ?? ' border border-slate-400',
                'aspect' => $browseColumnImage->getOption('aspect'),
            ])->render();

            return <<<BLADE
                <a href="$value" target="_blank">$renderedView</a>
            BLADE;
        };

        return $browseColumnImage;
    }

    /**
     * Set aspect ratio of the image
     *
     * @param  string  $aspect  Format 1/1 (width/height)
     */
    public function aspect(string $aspect): static
    {
        $this->options['aspect'] = $aspect;

        return $this;
    }
}
