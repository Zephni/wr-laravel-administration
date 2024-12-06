<?php
namespace WebRegulate\LaravelAdministration\Livewire\ManageableModels;

use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Classes\BrowseFilter;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;
use WebRegulate\LaravelAdministration\Classes\ManageableFields\Text;

class ManageableModelDynamicBrowseFilters extends Component
{
    /**
     * Manageable model class
     * 
     * @var string
     */
    public string $manageableModelClass;

    /**
     * Browse filter inputs
     * 
     * @var array
     */
    public array $browseFilterInputs = [
        
    ];

    public function updatedBrowseFilterInputs()
    {
        $this->dispatch('filtersUpdatedOutside', $this->browseFilterInputs);
    }

    /**
     * Mount the browse filters for the passed manageable model class
     * 
     * @param string $manageableModel
     */
    public function mount(string $manageableModelClass)
    {
        $this->manageableModelClass = $manageableModelClass;
        $this->browseFilterInputs = ManageableModel::getStaticOption($manageableModelClass, 'browse.defaultDynamicFilters');

        // If type or operator missing from any filter, set default values
        foreach($this->browseFilterInputs as $key => $item) {
            if(!isset($item['type'])) {
                $this->browseFilterInputs[$key]['type'] = 'Text';
            }

            if(!isset($item['operator'])) {
                $this->browseFilterInputs[$key]['operator'] = 'like';
            }
        }
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $tableColumns = $this->manageableModelClass::getTableColumns();
        $tableColumns = array_combine($tableColumns, $tableColumns);

        return view(WRLAHelper::getViewPath('livewire.manageable-models.dynamic-browse-filters'), [
            'browseFilters' => $this->getDynamicBrowseFilters(),
            'tableColumns' => $tableColumns,
        ]);
    }

    /**
     * Get dnyamic browse filters
     * 
     * @return array
     */
    public function getDynamicBrowseFilters(): array
    {
        $items = [];
        foreach($this->browseFilterInputs as $key => $item) {
            $dynamicBrowseFilter = static::buildBrowseFilter($item);
            $dynamicBrowseFilter->field->removeAttribute('wire:model.live');
            $dynamicBrowseFilter->field->setAttribute('wire:model.live.debounce.400ms', "browseFilterInputs.$key.value");
            $items[] = $dynamicBrowseFilter;
        }

        return $items;
    }

    /**
     * Build browse filter
     * 
     * @param string $field
     * @var array $item
     */
    public static function buildBrowseFilter(array $item): BrowseFilter
    {
        return new BrowseFilter(
            Text::makeBrowseFilter($item['field'])
                ->setLabel('&nbsp;')
                ->setOptions(['containerClass' => 'flex-1', 'labelClass' => ''])
                ->setAttributes([
                    'placeholder' => 'Search...',
                    'class' => '!mt-2'
                ]),
            function(Builder $query, $table, $columns, $value) use($item) {
                if(!empty($value)) {
                    if($item['operator'] == 'like') {
                        $query->where($table.'.'.$item['field'], 'like', '%'.$value.'%');
                    } elseif($item['operator'] == 'not like') {
                        $query->where($table.'.'.$item['field'], 'not like', '%'.$value.'%');
                    } else {
                        $query->where($table.'.'.$item['field'], $item['operator'], $value);
                    }
                }

                return $query;
            }
        );
    }

    /**
     * Apply filter
     * 
     * @return QueryBuilder
     */

    /**
     * Get next available column (one that isn't being used)
     * 
     * @return string
     */
    public function getNextAvailableColumn(): string
    {
        $columns = $this->manageableModelClass::getTableColumns();

        $usedColumns = array_map(function($item) {
            return $item['field'];
        }, $this->browseFilterInputs);

        foreach($columns as $column) {
            if(!in_array($column, $usedColumns)) {
                return $column;
            }
        }

        // If no column is available, return the last column
        return end($columns);
    }

    /**
     * Add filter action
     * 
     * @return void
     */
    public function addFilterAction()
    {
        $nextAvailableColumn = $this->getNextAvailableColumn();

        $this->browseFilterInputs[] = [
            'field' => $nextAvailableColumn,
            'type' => 'Text',
            'operator' => 'like',
            'value' => '',
        ];
    }
}