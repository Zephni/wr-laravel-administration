<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Croppable image manageable field.
 *
 * Specialises {@see Image} by:
 *   - Returning the raw stored value from {@see getValue()} (the croppable UI
 *     manages its own placeholders, so we skip the wrla_field_ formatting
 *     branch used by {@see Image::getValue()}).
 *   - Composing manipulation callbacks instead of replacing them — croppable
 *     images frequently need to layer post-crop processing on top of the
 *     cropping step that was registered earlier.
 *   - Rendering through the croppable-specific blade view.
 *
 * NOTE: For now you can only use 1 croppable image per manageable model form elements.
 */
class ImageCroppable extends Image
{
    /**
     * Get value
     */
    public function getValue(): string
    {
        return $this->getAttribute('value');
    }

    /**
     * Manipulate image — composes onto any existing callback rather than
     * replacing it.
     *
     * @return $this
     */
    public function manipulateImage(callable $callback): static
    {
        if ($this->manipulateImageFunction !== null) {
            $previous = $this->manipulateImageFunction;
            $this->manipulateImageFunction = function ($image) use ($previous, $callback) {
                return $callback($previous($image));
            };
        } else {
            $this->manipulateImageFunction = $callback;
        }

        return $this;
    }

    /**
     * Render hook: use the croppable image blade view.
     */
    protected function viewPath(): string
    {
        return WRLAHelper::getViewPath('components.forms.input-image-croppable');
    }
}
