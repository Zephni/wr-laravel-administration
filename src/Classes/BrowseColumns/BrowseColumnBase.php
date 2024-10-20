<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

use Illuminate\Database\Eloquent\Model;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class BrowseColumnBase
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
        'maxChars' => 50,
        'renderHtml' => false,
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
        $this->label = $this->buildLabel($label);
        $this->type = $type;
        $this->width = $width;

        if($type == 'image') {
            $this->allowOrdering(false);
            $this->renderHtml(true);
        }
    }

    /**
     * Build label (removes first part of relation.label or relation.something->label)
     *
     * @param string $label
     * @return string
     */
    private function buildLabel(string $label): string
    {
        $newLabel = str($label)->after('.');
        $newLabel = $newLabel->afterLast('->');
        return $newLabel;
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
     * Render html in the column (false by default)
     *
     * @param bool $renderHtml
     * @return static
     */
    public function renderHtml(bool $renderHtml = true): static
    {
        return $this->setOption('renderHtml', $renderHtml);
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
     * Override the render value method for this column, receives the value and the model as arguments.
     * Callback takes $value, $model as arguments and returns the rendered value as a string.
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

    static $test = 0;
    /**
     * Render the value of the column
     *
     * @param mixed $model
     * @param string $column
     * @return string
     */
    public function renderValue(mixed $model, string $column): string
    {
        $value = $this->interpretValueFromColumn($model, $column);

        // dd($model, $column, $this->getOption('value'), $useColumn, $model->{$column});

        if($this->overrideRenderValue)
        {
            return $this->renderFinalStringValue(call_user_func($this->overrideRenderValue, $value, $model));
        }

        if($this->type == 'string')
        {
            // Use maxChars option to truncate text
            if($this->options['maxChars'] != null && strlen($value) > $this->options['maxChars']) {
                $value = substr($value, 0, $this->options['maxChars']).'...';
            }

            return $this->renderFinalStringValue($value);
        }
        elseif($this->type == 'image')
        {
            $renderedView = view(WRLAHelper::getViewPath('components.forced-aspect-image', false), [
                "src" => $value,
                "class" => $this->getOption('class') ?? ' border border-slate-400',
                "aspect" => $this->getOption('aspect')
            ])->render();

            $value = <<<BLADE
                <a href="$value" target="_blank">$renderedView</a>
            BLADE;

            return $this->renderFinalStringValue($value);
        }

        return $this->renderFinalStringValue($value);
    }

    /**
     * Interpret value from column on the given model
     *
     * @param Model $model
     * @param string $column
     */
    public function interpretValueFromColumn(Model $model, string $column): mixed
    {
        // If value set, use that
        if($this->getOption('value')) {
            return $this->getOption('value');
        }

        // Otherwise, check if relationship and interpret
        if(WRLAHelper::isBrowseColumnRelationship($column)) {
            $relationshipParts = WRLAHelper::parseBrowseColumnRelationship($column);

            // If relationship row doesn't exist, return empty string
            if(!$model->{$relationshipParts[0]}) {
                return '';
            }

            // If relationship parts [1] has -> then it's a json column so we dig for the value
            if(str($relationshipParts[1])->contains('->')) {
                $dotNotationParts = explode('->', $relationshipParts[1]);
                $jsonField = $model->{$relationshipParts[0]}->{$dotNotationParts[0]};
                return data_get(json_decode($jsonField), implode('.', array_slice($dotNotationParts, 1)));
            }

            // Otherwise, just return the relationship value
            return $model->{$relationshipParts[0]}?->{$relationshipParts[1]} ?? '';
        }

        // Otherwise, just return the column value
        return $model->$column;
    }

    /**
     * Render string value
     *
     * @param ?string $value
     * @return string
     */
    public function renderFinalStringValue(?string $value): string
    {
        $value ??= '';

        if($this->options['renderHtml']) {
            return $value;
        }

        return htmlentities($value);
    }
}
