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
    public array $options = [];

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
     * Render the value of the column
     *
     * @param mixed $model
     * @param string $column
     * @return string
     */
    public function renderValue(mixed $model, string $column): string
    {
        // If $column has :: then we assume it has been selected and just take the second part, which should be rel_table.column
        $useColumn = (strpos($column, '::') !== false)
            ? explode('::', $column)[1]
            : $column;

        // If $useColumn is relationship on same table as $model, appen _other to table name
        if(count($useColumnTemp = explode('.', $useColumn)) > 1) {
            if($useColumnTemp[0] == $model->getTable()) {
                $useColumn = "$useColumnTemp[0]_other.$useColumnTemp[1]";
            }
        }
        
        $value = $this->getOption('value') ?? $model->{$useColumn};
        
        // dd($model, $column, $this->getOption('value'), $useColumn, $model->{$useColumn});

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