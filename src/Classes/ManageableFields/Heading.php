<?php

namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use WebRegulate\LaravelAdministration\Traits\ManageableField;

class Heading
{
    use ManageableField;

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @return string $class
     * @return static
     */
    public static function make(string $title, string $icon = 'fas fa-info-circle', $class = ''): BladeElement
    {
        $manageableField = new BladeElement(null, null, null);
        $manageableField->setOptions([
            'data' => [
                'title' => $title,
                'icon' => $icon,
                'class' => $class,
            ],
            'bladeCode' => <<<'BLADE'
                <div class="{{ $class }} relative top-1 w-full first:mt-0 mt-3">
                    <div class="flex items-center gap-2.5 text-xl">
                        <i class="{{ $icon }} relative top-[-2px] text-slate-700 dark:text-white"></i>
                        <h3>{{ $title }}</h3>
                    </div>
                    <hr class="mt-1.5 border-t border-slate-500" />
                </div>
            BLADE,
        ]);

        return $manageableField;
    }
}
