<?php
namespace WebRegulate\LaravelAdministration\Classes\ManageableFields;

use Exception;
use Illuminate\Support\Facades\Blade;
use WebRegulate\LaravelAdministration\Traits\ManageableField;
use WebRegulate\LaravelAdministration\Classes\ManageableModel;

class Heading
{
    use ManageableField;

    /**
     * Make method (can be used in any class that extends FormComponent).
     *
     * @param string $title
     * @param string $icon
     * @return string $class
     * @return static
     */
    public static function make(string $title, string $icon = 'fas fa-info-circle', $class = ''): BladeElement
    {
        $manageableField = new BladeElement('__wrla_no_name__', null, null);
        $manageableField->setOptions([
            'data' => [
                'title' => $title,
                'icon' => $icon,
                'class' => $class,
            ],
            'bladeCode' => <<<BLADE
                <div class="{{ \$class }} relative top-1 w-full first:mt-0 mt-3">
                    <div class="flex items-center gap-2.5 text-xl">
                        <i class="{{ \$icon }} relative top-[-2px] text-slate-700 dark:text-white"></i>
                        <h3>{{ \$title }}</h3>
                    </div>
                    <hr class="mt-1.5 border-t border-slate-500" />
                </div>
            BLADE,
        ]);

        return $manageableField;
    }
}
