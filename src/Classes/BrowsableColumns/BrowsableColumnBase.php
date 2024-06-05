<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowsableColumns;

use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class BrowsableColumnBase
{
    /**
     * Label of the column, defaults to title version of the column name
     *
     * @var string|null
     */
    public ?string $label = null;

    /**
     * Type of the column display, defaults to string
     *
     * @var string
     */
    public string $type = 'string';

    /**
     * Width of the column, defaults to natural table column width
     *
     * @var null|int|string
     */
    public null|int|string $width = null;

    /**
     * Options for the column
     *
     * @var array
     */
    public array $options = [
        'allowOrdering' => true,
    ];

    /**
     * Callback to override the render value of the column, receives the value and the model as arguments
     *
     * @var callable|null
     */
    public $overrideRenderValue = null;

    /**
     * Constructor
     *
     * @param string|null $label
     * @param string $type
     * @param null|integer|string|null $width
     */
    public function __construct(?string $label, string $type, null|int|string $width = null)
    {
        $this->label = $label;
        $this->type = $type;
        $this->width = $width;

        if($type == 'image') {
            $this->allowOrdering(false);
        }
    }

    /**
     * Set a single option for the column
     *
     * @param mixed $key
     * @param mixed $value
     * @return static
     */
    public function setOption(mixed $key, mixed $value): static
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * Get an option from the column
     *
     * @param mixed $key
     * @return mixed
     */
    public function getOption($key)
    {
        return $this->options[$key] ?? null;
    }

    /**
     * Set multiple options for the column (merged with existing options)
     *
     * @param array $options
     * @return static
     */
    public function setOptions(array $options): static
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Get all options for the column
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Allow ordering by this column (true by default)
     *
     * @param bool $allowOrdering
     * @return static
     */
    public function allowOrdering(bool $allowOrdering = true): static
    {
        return $this->setOption('allowOrdering', $allowOrdering);
    }

    /**
     * Render the label of the column
     *
     * @return string
     */
    public function renderLabel(): string
    {
        return $this->label;
    }

    /**
     * Override the render value method for this column, receives the value and the model as arguments
     *
     * @param callable $callback
     * @return static
     */
    public function overrideRenderValue(callable $callback): static
    {
        $this->overrideRenderValue = $callback;
        return $this;
    }

    /**
     * Render display name of the column
     *
     * @return string
     */
    public function renderDisplayName(): string
    {
        return $this->label;
    }

    /**
     * Render the value of the column
     *
     * @param mixed $model
     * @param string $column
     * @return string
     */
    public function renderValue(mixed $model, string $column): string
    {
        // If column is a relationship, modify the column to include the relationship
        if(WRLAHelper::isBrowsableColumnRelationship($column)) {
            $relationshipData = WRLAHelper::parseBrowsableColumnRelationship($column);
            if($relationshipData['table'] == $model->getTable()) {
                $column = "{$relationshipData['table']}_other.{$relationshipData['column']}";
            } else {
                $column = "{$relationshipData['table']}.{$relationshipData['column']}";
            }
        }

        // Get value if set in option, if not the use the value from the models column
        $value = $this->getOption('value') ?? $model->{$column};

        // dd($model, $column, $this->getOption('value'), $useColumn, $model->{$column});

        if($this->overrideRenderValue)
        {
            return call_user_func($this->overrideRenderValue, $value, $model);
        }

        if($this->type == 'string')
        {
            return $value ?? '';
        }
        elseif($this->type == 'image')
        {
            $renderedView = view(
                WRLAHelper::getViewPath('components.forced-aspect-image', false), [
                "src" => $value,
                "class" => $this->getOption('containerClass') ?? 'border-2 border-primary-600',
                "imageClass" => 'wrla_image_preview '.$this->getOption('imageClass') ?? '',
                "aspect" => $this->getOption('aspect'),
                "rounded" => $this->getOption('rounded') ?? false,
            ])->render();

            return <<<BLADE
                <a href="{{ $value }}" target="_blank">$renderedView</a>
            BLADE;
        }

        return $value;
    }
}
