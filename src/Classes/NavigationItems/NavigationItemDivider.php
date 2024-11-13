<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

class NavigationItemDivider extends NavigationItem
{
    // Custom html
    public NavigationItemHTML $navigationItemHTML;

    public function __construct(
        public string $title = '',
        public string $icon = ''
    ) {
        parent::__construct(
            'wrla::html',
            null,
            '',
            $icon,
            []
        );

        if(!empty($this->icon) || !empty($this->title)) {
            $icon = !empty($this->icon) ? "<i class=\"$this->icon\"></i>" : '';
            $html = <<<HTML
                <div class="flex items-center justify-center">
                    <div class="flex items-center justify-center w-1/2 border-t border-slate-550"></div>
                    <div class="mx-3 text-slate-400 text-sm font-semibold">
                        $icon
                        $this->title
                    </div>
                    <div class="flex items-center justify-center w-1/2 border-t border-slate-550"></div>
                </div>
            HTML;
        } else {
            $html = <<<HTML
                <div class="w-10/12 border-t border-slate-550 mx-auto my-2"></div>
            HTML;
        }

        $this->navigationItemHTML = new NavigationItemHTML(<<<HTML
            $html
        HTML);
    }

    /**
     * Overriden render method
     * 
     * @return string
     */
    public function render(): string
    {
        return $this->navigationItemHTML->render();
    }
}
