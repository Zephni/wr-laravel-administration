<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

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
