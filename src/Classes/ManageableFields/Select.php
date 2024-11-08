<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Traits\ManageableField;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class Select
{
    use ManageableField;

    /**
     * Items
     *
     * @var array
     */
    protected array $items = [];

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

        $this->setToFirstValueIfNotSet();

        return $this;
    }

    /**
     * Set items from collection key and value.
     *
     * @param Collection $collection eg. User::all()
     * @param string $key eg. 'id'
     * @param string $value eg. 'name'
     * @return $this
     */
    public function setItemsFromCollection(Collection $collection, string $key, string $value): static
    {
        $this->items = $collection->pluck($value, $key)->toArray();

        $this->setToFirstValueIfNotSet();

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

        if ($model instanceof \Illuminate\Database\Eloquent\Model) {
            // Do nothing
        } else if($model instanceof ManageableModel) {
            $model = $model->getModelInstance();
        } else {
            throw new \Exception("In Select ManageableField: Model must be an instance of ManageableModel");
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

            $this->setToFirstValueIfNotSet();
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
        $this->setToFirstValueIfNotSet();

        return view(WRLAHelper::getViewPath('components.forms.input-select'), [
            'label' => $this->getLabel(),
            'options' => $this->options,
            'items' => $this->items,
            'attributes' => new ComponentAttributeBag(array_merge($this->htmlAttributes, [
                'name' => $this->getAttribute('name'),
                'value' => $this->getValue()
            ])),
        ])->render();
    }
}
