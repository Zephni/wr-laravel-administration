<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

class NavigationItem
{
    public static $indexTotal;

    public int $index = 0;

    /**
     * Constructor
     *
     * @param string $route
     * @param string $name
     * @param string $icon
     * @param array $children
     */
    public function __construct(
        public ?string $route,
        public string $name,
        public string $icon = 'fa fa-question',
        public array $children = [],
    ) {
        // Increment index
        self::$indexTotal++;

        // Set index
        $this->index = self::$indexTotal;
    }

    /**
     * Has children
     * @return bool
     */
    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    /**
     * Get Url
     * @return string
     */
    public function getUrl($prependPackagePrefix = true): string
    {
        if($prependPackagePrefix) {
            return route('wrla.'.$this->route);
        }

        return route($this->route);
    }

    /**
     * Render the navigation item
     * @return string
     */
    public function render(): string
    {
        return $this->name.'<br />';
    }
}
