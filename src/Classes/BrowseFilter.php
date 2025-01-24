<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use WebRegulate\LaravelAdministration\Traits\ManageableField;

class BrowseFilter
{
    /**
     * The field to filter by.
     *
     * @var ManageableField|callable
     */
    public mixed $field;

    /**
     * The applicable filter. Must take parameters:
     * Eloquent/Builder $query
     * Collection $columns
     * mixed $value
     *
     * Return the Builder with filtered results.
     *
     * @var callable
     */
    public $applicableFilter;

    /**
     * Create a new BrowseFilter instance.
     *
     * @param ManageableField|callable $field
     * @param callable $applicableFilter
     */
    public function __construct($field, callable $applicableFilter)
    {
        $this->field = $field;
        $this->applicableFilter = $applicableFilter;
    }

    /**
     * Get key from field's attribute name.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->getField()->getAttribute('name');
    }

    /**
     * Get field.
     *
     * @return ManageableField
     */
    public function getField()
    {
        if(is_callable($this->field)) {
            return call_user_func($this->field);
        }

        return $this->field;
    }

    /**
     * Render the filter.
     *
     * @return string
     */
    public function render(): string
    {
        // Get field
        $field = $this->getField();

        // Build HTML
        $prependHTML = '';

        // If newRow option is set, prepend a new row
        if($field->getOption('newRow')) {
            $prependHTML .= '<div class="basis-full"></div>';
        }

        return $prependHTML.$field->render();
    }

    /**
     * Apply to query builder.
     *
     * @param Builder $query
     * @param string $table
     * @param Collection $columns
     * @param mixed $value
     * @return Builder
     */
    public function apply(Builder $query, string $table, Collection $columns, mixed $value): Builder
    {
        return call_user_func($this->applicableFilter, $query, $table, $columns, $value);
    }
}
