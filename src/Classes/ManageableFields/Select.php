<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Database\Eloquent\Collection;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

/**
 * Class Input
 *
 * This class is responsible for generating input fields.
 */
class Select extends ManageableField
{
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
     * @param array $items
     * @return $this
     */
    public function setItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Set items from model key and value.
     *
     * @param Collection $eloquentCollection eg. User::all()
     * @param string $key eg. 'id'
     * @param string $value eg. 'name'
     * @return $this
     */
    public function setItemsFromEloquentCollection(Collection $eloquentCollection, string $key, string $value): static
    {
        $this->items = $eloquentCollection->pluck($value, $key)->toArray();

        return $this;
    }

    /**
     * Render the input field.
     *
     * @param PageType $upsertType
     * @return mixed
     */
    public function render(PageType $upsertType): mixed
    {
        return view(WRLAHelper::getViewPath('components.forms.input-select'), [
            'name' => $this->attributes['name'],
            'label' => $this->getLabel(),
            'value' => $this->attributes['value'],
            'options' => $this->options,
            'items' => $this->items,
            'attr' => collect($this->attributes)
                ->forget(['name', 'value', 'type'])
                ->toArray(),
        ])->render();
    }
}
