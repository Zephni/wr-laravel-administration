<?php

namespace WebRegulate\LaravelAdministration\Classes\BrowseColumns;

use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use function Laravel\Prompts\select;

class BrowseColumn extends BrowseColumnBase
{
    /**
     * Create a new instance of the class
     * 
     * @param string|null $label Label of the column, defaults to title version of the column name
     * @param ?callable $overrideRenderValue Callback to override the render value of the column, receives the value and the model as arguments, must return a string value
     */
    public static function make(?string $label, ?callable $overrideRenderValue = null): static
    {
        $browseColumn = new self($label);

        if(!is_null($overrideRenderValue)) {
            $browseColumn->overrideRenderValue($overrideRenderValue);
        }

        return $browseColumn;
    }

    /**
     * Limit characters
     */
    public function limit(int $limit, string $end = '...'): static
    {
        $this->overrideRenderValue(function($value) use ($limit, $end) {
            return str($value)->limit($limit, $end);
        });

        return $this;
    }

    /**
     * Use query to set the value of the column, this will be prepended to the base query, make sure to select your value as the column/key name
     */
    public static function query(?string $label, string $static, callable $query): static
    {
        $browseColumn = new self($label);

        $static::appendPreQuery($query);

        return $browseColumn;
    }

    /**
     * Use query to set the value of the column, this will be prepended to the base query, make sure to select your value as the column/key name
     * 
     * @param string|callable $sql The raw SQL string or a callable that takes $filters and returns the SQL string
     */
    public static function selectRaw(?string $label, string $static, string|callable $sql): static
    {
        $browseColumn = new self($label);

        self::query($label, $static, function($query, $filters) use ($sql) {
            if(is_callable($sql)) {
                $sql = $sql($filters);
            }

            $query->selectRaw($sql);
        });

        return $browseColumn;
    }
}
