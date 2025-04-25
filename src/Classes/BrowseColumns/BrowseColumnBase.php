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
     * Options for the column
     *
     * @var array
     */
    public array $options = [
        'allowOrdering' => true,
        'maxChars' => 80,
        'renderHtml' => false,
        'width' => null, // null|int|string
        'minWidth' => null, // null|int|string
        'maxWidth' => null, // null|int|string
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
     */
    public function __construct(?string $label)
    {
        $this->label = $this->buildLabel($label);
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
        // If no callback set, set it
        if($this->overrideRenderValue === null)
        {
            $this->overrideRenderValue = $callback;
        }
        // If already set, merge the callbacks
        elseif (is_callable($this->overrideRenderValue))
        {
            $oldCallback = $this->overrideRenderValue;
            $this->overrideRenderValue = function($value, $model) use ($oldCallback, $callback) {
                $value = call_user_func($oldCallback, $value, $model);
                return call_user_func($callback, $value, $model);
            };
        }
        return $this;
    }

    /**
     * Set date format render value
     *
     * @param string $format
     * @return static
     */
    public function setDateFormat(string $format): static
    {
        return $this->overrideRenderValue(fn($value) => $value->format($format));
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

        // If override render value set, return that
        if($this->overrideRenderValue !== null) {
            return $this->renderFinalStringValue(call_user_func($this->overrideRenderValue, $value, $model));
        }

        // Use maxChars option to truncate text
        if($this->options['maxChars'] != null && strlen((string) $value) > $this->options['maxChars']) {
            $value = substr((string) $value, 0, $this->options['maxChars']).'...';
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
            try {
                if(!$model->{$relationshipParts[0]}) {
                    return '';
                }
            } catch (\Exception) {
                return '';
            }

            // If relationship parts [1] has -> then it's a json column so we dig for the value
            if(str($relationshipParts[1])->contains('->')) {
                $dotNotationParts = explode('->', (string) $relationshipParts[1]);
                $jsonField = $model->{$relationshipParts[0]}->{$dotNotationParts[0]};
                return data_get(json_decode((string) $jsonField), implode('.', array_slice($dotNotationParts, 1)));
            }

            // Otherwise, just return the relationship value
            return $model->{$relationshipParts[0]}?->{$relationshipParts[1]} ?? '';
        }

        // Otherwise, just return the column value
        return $model->$column;
    }

    /**
     * Set class
     * 
     * @param string $class
     * @return static
     */
    public function class(string $class): static
    {
        $this->setOption('class', $class);
        return $this;
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
