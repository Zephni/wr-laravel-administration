<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Database\Eloquent\Builder;
use WebRegulate\LaravelAdministration\Classes\ManageableFields\ManageableField;
use WebRegulate\LaravelAdministration\Enums\PageType;

class Filterable
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
     * Create a new Filterable instance.
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
     * @param mixed $value
     * @return Builder
     */
    public function apply(Builder $query, $columns, $value): Builder
    {
        return call_user_func($this->applicableFilter, $query, $columns, $value);
    }
}