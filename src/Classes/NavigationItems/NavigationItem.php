<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

class NavigationItem
{
    /**
     * Navigation items to be passed to views. Setup in config/wr-laravel-administration.php and finalized in WRLAServiceProvider.php
     *
     * @var array
     */
    public static $navigationItems = [];

    // Index total
    public static $indexTotal;

    // Index
    public int $index = 0;

    /**
     * Constructor
     *
     * @param string $route
     * @param array $routeData
     * @param string $name
     * @param string $icon
     * @param array $children
     */
    public function __construct(
        public string $route,
        public array $routeData,
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
            return route($this->route, $this->routeData);
        }

        return route($this->route, $this->routeData);
    }

    /**
     * Render the navigation item
     * @return string
     */
    public function render(): string
    {
        return $this->name;
    }
}
