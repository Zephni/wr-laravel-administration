<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use WebRegulate\LaravelAdministration\Enums\PageType;
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
     * Get field.
     * 
     * @param array $filterKeyValues
     * @return ManageableField
     */
    public function getField($filterKeyValues)
    {
        return !is_callable($this->field)
            ? $this->field
            : call_user_func($this->field, $filterKeyValues);
    }

    /**
     * Render the filter.
     * 
     * @param array $filterKeyValues
     * @return string
     */
    public function render(array $filterKeyValues): string
    {
        return $this->getField($filterKeyValues)->render(PageType::BROWSE);
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