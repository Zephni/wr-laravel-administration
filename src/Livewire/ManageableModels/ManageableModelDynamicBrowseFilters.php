<?php

namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use WebRegulate\LaravelAdministration\Classes\BrowseFilter;
use WebRegulate\LaravelAdministration\Classes\ManageableFields\Text;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class ManageableModelDynamicBrowseFilters extends Component
{
    /**
     * Manageable model class
     */
    public string $manageableModelClass;

    /**
     * Browse filter inputs
     */
    public array $browseFilterInputs = [];

    public function updatedBrowseFilterInputs()
    {
        $this->dispatch('filtersUpdatedOutside', $this->browseFilterInputs);

        // Remember filters - Temporarily disabled
        // $rememberFilters = $this->manageableModelClass::getStaticOption($this->manageableModelClass, 'rememberFilters');
        // if($rememberFilters['enabled']) {
        //     $sessionKey = "wrla_dynamicFilters_{$this->manageableModelClass}";    
        //     session([$sessionKey => $this->browseFilterInputs]);
        // }
    }

    /**
     * Mount the browse filters for the passed manageable model class
     */
    public function mount(string $manageableModelClass)
    {
        $this->manageableModelClass = $manageableModelClass;
        $this->browseFilterInputs = ManageableModel::getStaticOption($manageableModelClass, 'browse.defaultDynamicFilters');

        // If type or operator missing from any filter, set default values
        foreach ($this->browseFilterInputs as $key => $item) {
            if (! isset($item['type'])) {
                $this->browseFilterInputs[$key]['type'] = 'Text';
            }

            if (! isset($item['operator'])) {
                $this->browseFilterInputs[$key]['operator'] = 'contains';
            }
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        $tableColumns = $this->manageableModelClass::getTableColumns();
        $tableColumns = array_combine($tableColumns, $tableColumns);

        // Remember filters - Temporarily disabled
        // $rememberFilters = $this->manageableModelClass::getStaticOption($this->manageableModelClass, 'rememberFilters');
        // if($rememberFilters['enabled']) {
        //     $sessionKey = "wrla_dynamicFilters_{$this->manageableModelClass}";
        //     $dynamicFiltersSessionValue = session($sessionKey);
        //     foreach ($dynamicFiltersSessionValue ?? [] as $index => $item) {
        //         $this->browseFilterInputs[$index]['type'] = 'Text';
        //         $this->browseFilterInputs[$index]['field'] = $item['field'];
        //         $this->browseFilterInputs[$index]['operator'] = $item['operator'];
        //         $this->browseFilterInputs[$index]['value'] = !empty($item['value'])
        //             ? (json_decode($item['value'], true) ?? $item['value'])
        //             : $item['value'] ?? '';
        //     }
        // }

        return view(WRLAHelper::getViewPath('livewire.manageable-models.dynamic-browse-filters'), [
            'browseFilters' => $this->getDynamicBrowseFilters(),
            'tableColumns' => $tableColumns,
        ]);
    }

    /**
     * Get dnyamic browse filters
     */
    public function getDynamicBrowseFilters(): array
    {
        $items = [];
        foreach ($this->browseFilterInputs as $key => $item) {
            $dynamicBrowseFilter = static::buildBrowseFilter($item);
            $dynamicBrowseFilter->field->removeAttribute('wire:model.live');
            $dynamicBrowseFilter->field->setAttribute('wire:model.live.debounce.400ms', "browseFilterInputs.$key.value");
            $items[] = $dynamicBrowseFilter;
        }

        return $items;
    }

    /**
     * Build browse filter
     */
    public static function buildBrowseFilter(array $item): BrowseFilter
    {
        return Text::makeBrowseFilter($item['field'])
            ->setLabel('')
            ->setOptions(['containerClass' => 'flex-1', 'labelClass' => ''])
            ->setAttributes([
                'placeholder' => 'Search...',
                'autocomplete' => 'off',
                'autocorrect' => 'off',
                'spellcheck' => 'false',
                'data-lpignore' => 'true',
            ])
            ->browseFilterApply(function (Builder $query, $table, $columns, $value) use ($item) {
                // Split value by | for OR condition
                $orValues = explode('|', $value);

                $query->where(function ($query) use ($orValues, $table, $item): void {
                    foreach ($orValues as $orValue) {
                        $orValue = trim($orValue ?? '');

                        // Split value by , for AND condition
                        $andValues = explode(',', $orValue);

                        $query->orWhere(function ($query) use ($andValues, $table, $item): void {
                            // If $andValues is empty, pass array with empty string
                            if (count($andValues) === 1 && empty($andValues[0])) {
                                $andValues = [''];
                            }

                            // Loop through each value
                            foreach ($andValues as $andValue) {
                                $andValue = trim($andValue);

                                // Safely match operator
                                $operator = match ($item['operator']) {
                                    'contains' => 'contains',
                                    'not contains' => 'not contains',
                                    'like' => 'like',
                                    'not like' => 'not like',
                                    '=' => '=',
                                    '!=' => '!=',
                                    '>' => '>',
                                    '<' => '<',
                                    '>=' => '>=',
                                    '<=' => '<=',
                                    'empty' => 'empty',
                                    'not empty' => 'not empty',
                                    default => 'contains',
                                };

                                // If empty or not empty, apply and skip
                                if ($operator == 'empty' || $operator == 'not empty') {
                                    $query->where(function ($query) use ($table, $item, $operator): void {
                                        if ($operator == 'empty') {
                                            $query->whereNull($table.'.'.$item['field'])
                                                ->orWhere($table.'.'.$item['field'], '=', '');
                                        } else {
                                            $query->whereNotNull($table.'.'.$item['field'])
                                                ->where($table.'.'.$item['field'], '!=', '');
                                        }
                                    });

                                    return;
                                }

                                // If value empty, skip
                                if (empty($andValue)) {
                                    return;
                                }

                                // If operator is 'contains' or 'not contains', modify operator and wrap value with %value%
                                if ($operator == 'contains' || $operator == 'not contains') {
                                    $operator = $operator == 'contains' ? 'like' : 'not like';
                                    $andValue = '%'.$andValue.'%';
                                }

                                // If not like, we need to also check for null values and skip
                                if ($operator == 'not like') {
                                    $query->where(function ($query) use ($table, $item, $andValue): void {
                                        $query->where($table.'.'.$item['field'], 'not like', $andValue)
                                            ->orWhereNull($table.'.'.$item['field']);
                                    });

                                    return;
                                }

                                // Otherwise just apply the operator
                                $query->where($table.'.'.$item['field'], $operator, $andValue);
                            }
                        });
                    }
                });

                return $query;
            });
    }

    /**
     * Get next available column (one that isn't being used)
     */
    public function getNextAvailableColumn(): string
    {
        $columns = $this->manageableModelClass::getTableColumns();

        $usedColumns = array_map(fn ($item) => $item['field'], $this->browseFilterInputs);

        foreach ($columns as $column) {
            if (! in_array($column, $usedColumns)) {
                return $column;
            }
        }

        // If no column is available, return the last column
        return end($columns);
    }

    /**
     * Add filter action
     */
    public function addFilterAction()
    {
        $nextAvailableColumn = $this->getNextAvailableColumn();

        $this->browseFilterInputs[] = [
            'field' => $nextAvailableColumn,
            'type' => 'Text',
            'operator' => 'contains',
            'value' => '',
        ];
    }

    /**
     * Remove filter action
     */
    public function removeFilterAction(int $index)
    {
        unset($this->browseFilterInputs[$index]);
        $this->browseFilterInputs = array_values($this->browseFilterInputs);
    }
}
