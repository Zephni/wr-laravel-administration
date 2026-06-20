<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

/**
 * Lightweight item (inner field) definition for the {@see MultiField} repeater field.
 *
 * Each MultiFieldItem describes a single editable field that is repeated for every form
 * group the user adds. Items render side by side (just like the MultiImage grid) and their
 * `key` becomes the key used in the resulting JSON for each form group.
 *
 * Built in types: text, date, number, url, email, color, time, datetime-local,
 * textarea, select, image. Any other HTML input `type` may also be passed to
 * {@see make()} to allow custom inner fields.
 */
class MultiFieldItem
{
    /**
     * The JSON / array key this item maps to within each form group.
     */
    public string $key;

    /**
     * Item type. Either a built in type (textarea, select, image) or any HTML input type.
     */
    public string $type;

    /**
     * Field label rendered above the item. Null hides the label.
     */
    public ?string $label;

    /**
     * Extra HTML attributes applied to the inner input/select/textarea (eg. placeholder, min, step).
     */
    public array $attributes = [];

    /**
     * Option items for select items, in key => display value format.
     */
    public array $items = [];

    /**
     * Tailwind class controlling the width/sizing of this item within the form group.
     */
    public string $itemClass = 'flex-1';

    /**
     * Default value applied to new form groups for this item.
     */
    public mixed $default = '';

    /**
     * Storage disk for image items.
     */
    public string $fileSystem = 'public';

    /**
     * Storage path (relative to the disk) for image items.
     */
    public string $path = 'images';

    /**
     * Filename template / resolver for image items. May contain {id}, {index}, {time}
     * placeholders, or be a callable taking (model, originalFilename, groupIndex).
     */
    public mixed $filename = null;

    /**
     * Validation rules applied to each uploaded image (client side, within the Livewire component).
     */
    public string|array $uploadValidation = 'image|mimes:jpeg,png,jpg,gif|max:10240';

    public function __construct(string $key, string $type = 'text', ?string $label = null)
    {
        $this->key = $key;
        $this->type = $type;
        $this->label = $label;
    }

    /**
     * Generic factory. $type may be any built in type or HTML input type.
     */
    public static function make(string $key, string $type = 'text', ?string $label = null): static
    {
        return new static($key, $type, $label);
    }

    /**
     * Text input item.
     */
    public static function text(string $key, ?string $label = null): static
    {
        return new static($key, 'text', $label);
    }

    /**
     * Date input item. Stored/displayed as Y-m-d.
     */
    public static function date(string $key, ?string $label = null): static
    {
        return new static($key, 'date', $label);
    }

    /**
     * Number input item.
     */
    public static function number(string $key, ?string $label = null): static
    {
        return new static($key, 'number', $label);
    }

    /**
     * URL input item.
     */
    public static function url(string $key, ?string $label = null): static
    {
        return new static($key, 'url', $label);
    }

    /**
     * Textarea item.
     */
    public static function textarea(string $key, ?string $label = null): static
    {
        return new static($key, 'textarea', $label);
    }

    /**
     * Select item. $items is a key => display value array.
     */
    public static function select(string $key, array $items = [], ?string $label = null): static
    {
        return (new static($key, 'select', $label))->items($items);
    }

    /**
     * Image upload item.
     *
     * @param null|string|callable $filename Filename template ({id}, {index}, {time}) or callable(model, originalName, groupIndex).
     */
    public static function image(
        string $key,
        ?string $label = null,
        string $path = 'images',
        null|string|callable $filename = null,
        string $fileSystem = 'public',
    ): static {
        $item = new static($key, 'image', $label);
        $item->path = rtrim($path, '/');
        $item->filename = $filename;
        $item->fileSystem = $fileSystem;

        return $item;
    }

    /**
     * Set the item label.
     */
    public function label(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Merge extra HTML attributes onto the inner input.
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    /**
     * Set a single HTML attribute on the inner input.
     */
    public function attribute(string $key, string $value): static
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Convenience helper to set the placeholder attribute.
     */
    public function placeholder(string $placeholder): static
    {
        $this->attributes['placeholder'] = $placeholder;
        return $this;
    }

    /**
     * Set select option items (key => display value).
     */
    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Set the Tailwind sizing class for this item.
     */
    public function itemClass(string $itemClass): static
    {
        $this->itemClass = $itemClass;
        return $this;
    }

    /**
     * Set the default value applied to new form groups.
     */
    public function default(mixed $default): static
    {
        $this->default = $default;
        return $this;
    }

    /**
     * Set the storage disk for an image item.
     */
    public function fileSystem(string $fileSystem): static
    {
        $this->fileSystem = $fileSystem;
        return $this;
    }

    /**
     * Set the storage path for an image item.
     */
    public function path(string $path): static
    {
        $this->path = rtrim($path, '/');
        return $this;
    }

    /**
     * Set the filename template / resolver for an image item.
     */
    public function filename(null|string|callable $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Set the per-image upload validation rules.
     */
    public function uploadValidation(string|array $uploadValidation): static
    {
        $this->uploadValidation = $uploadValidation;
        return $this;
    }

    /**
     * Whether this item is an image upload item.
     */
    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    /**
     * Serialisable representation passed to the Livewire component / blade view.
     * Excludes the filename resolver (which may be a closure and is only needed
     * server side when storing the uploaded file).
     */
    public function toViewArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->label,
            'attributes' => $this->attributes,
            'items' => $this->items,
            'itemClass' => $this->itemClass,
            'default' => $this->default,
            'isImage' => $this->isImage(),
            'uploadValidation' => $this->uploadValidation,
        ];
    }
}
