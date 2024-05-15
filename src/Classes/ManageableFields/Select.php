<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Illuminate\Support\Collection;
use Illuminate\View\ComponentAttributeBag;
use WebRegulate\LaravelAdministration\Enums\PageType;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

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
            'label' => $this->getLabel(),
            'value' => $this->getValue(),
            'options' => $this->options,
            'items' => $this->items,
            'attributes' => new ComponentAttributeBag(array_merge($this->attributes, [
                'name' => $this->attributes['name']
            ])),
        ])->render();
    }
}
