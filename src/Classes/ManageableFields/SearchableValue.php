<?php
namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Traits\ManageableField;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class SearchableValue
{
    use ManageableField;

    public array $items = [];
    public array $filteredItems = [];

    /**
     * Post constructed method, called after name and value attributes are set.
     *
     * @return $this
     */
    public function postConstructed(): mixed
    {
        $this->setAttribute('wire:model.live', "livewireData.{$this->getAttribute('name')}");
        return $this;
    }

    /**
     * Set items for the options list. $items must use the following format:
     * key => display_value,...
     *
     * @param array|Collection $items
     * @return $this
     */
    public function setItems(array|Collection $items): static
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Set items from model, with optional query amd prepended all option.
     *
     * @param string $modelClass
     * @param string $displayColumn
     * @param ?callable $queryBuilderFunction Takes query builder as argument and returns query builder
     * @param ?callable $postModifyFunction Takes items array as argument and returns items array
     * @return $this
     */
    public function setItemsFromModel(string $modelClass, string $displayColumn, ?callable $queryBuilderFunction = null, ?callable $postModifyFunction = null): static
    {
        // Get model instance, if it's a manageable model then get the model instance
        $model = new $modelClass;

        if($model instanceof ManageableModel) {
            $model = $model->getModelInstance();
        }

        $table = $model->getTable();
        $query = $model::query();

        if ($queryBuilderFunction != null) {
            $query = $queryBuilderFunction($query);
            $query->addSelect("$table.id");
        } else {
            $query->select('id', $displayColumn);
        }

        try
        {
            $this->items = $query->pluck($displayColumn, "$table.id")->toArray();

            if ($postModifyFunction !== null) {
                // $this->items = ['all' => 'All'] + $this->items;
                $this->items = $postModifyFunction($this->items);
            }
        }
        catch (\Exception $e)
        {
            throw new \Exception("Error in Select->setItemsFromModel on table '$table': ". $e->getMessage());
        }

        return $this;
    }

    /**
     * Set to first value in items if value not set
     *
     * @return $this
     */
    public function setToFirstValueIfNotSet(): static
    {
        // If items is empty, return
        if (empty($this->items)) {
            return $this;
        }

        // If $this->htmlAttributes['value'] is not set, set it to the first key in the items array
        if (!isset($this->htmlAttributes['value']) || empty($this->getAttribute('value'))) {
            $this->setAttribute('value', array_key_first($this->items));
        }

        return $this;
    }

    /**
     * Render the input field.
     *
     * @return mixed
     */
    public function render(): mixed
    {
        // Get the searchable value field value
        $searchFieldValue = self::getField("searchable_value_{$this->getAttribute('name')}");
        
        // If null set to empty string (on first render)
        if($searchFieldValue == null) {
            self::setField("searchable_value_{$this->getAttribute('name')}", '');
        }

        // If search field value is not empty, filter the items
        if($searchFieldValue != '') {
            $this->filteredItems = [];
            foreach($this->items as $key => $value) {
                if(str($value)->contains($searchFieldValue, true)) {
                    $this->filteredItems[$key] = $value;
                }
            }
        }

        // Get attributes for search field and main input field
        $attributes = collect($this->htmlAttributes)->except(['placeholder'])->toArray();

        // Render the view
        return view(WRLAHelper::getViewPath('components.forms.searchable-value'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'items' => $this->items,
            'filteredItems' => $this->filteredItems,
            'fields' => self::$fields,
            'searchFieldValue' => $searchFieldValue,
            'valueIsSet' => $this->getAttribute('value') != null,
            'searchAttributes' => new ComponentAttributeBag([
                'wire:model.live' => "livewireData.searchable_value_{$attributes['name']}",
                'placeholder' => $this->getAttribute('placeholder') ?? 'Search...',
            ]),
            'attributes' => new ComponentAttributeBag(collect($this->htmlAttributes)-> merge([
                'type' => 'hidden',
                'name' => $this->getAttribute('name'),
                'value' => $this->getValue(),
            ])->toArray()),

        ])->render();
    }
}