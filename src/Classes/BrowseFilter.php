<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\ManageableFields\ManageableField;

class BrowseFilter
{
    /**
     * The field to filter by.
     *
     * @var ManageableField
     */
    public ManageableField $field;
    
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
     * @param ManageableField $field
     * @param callable $applicableFilter
     */
    public function __construct(ManageableField $field, callable $applicableFilter)
    {
        $this->field = $field;
        $this->applicableFilter = $applicableFilter;
    }

    /**
     * Render the filter.
     * 
     * @return string
     */
    public function render(): string
    {
        return $this->field->render(PageType::BROWSE);
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