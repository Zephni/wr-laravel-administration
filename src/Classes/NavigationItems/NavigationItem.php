<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

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

    // Show on condition callback
    private $showOnConditionCallback = null;

    // Enable on condition callback
    private $enableOnConditionCallback = null;

    // Open in new tab
    public bool $openInNewTab = false;

    /**
     * Constructor
     */
    public function __construct(
        public ?string $route,
        public ?array $routeData,
        public string $name,
        public string $icon = 'fa fa-question',
        public array $children = [],
    ) {
        // Increment index
        static::$indexTotal++;

        // Set index
        $this->index = static::$indexTotal;
    }

    /**
     * Make (static constructor)
     */
    public static function make(?string $route, ?array $routeData, string $name, string $icon = 'fa fa-question', array $children = []): static
    {
        // Replace any classes (strings) by calling getNavigationItem() method on each
        foreach ($children as $key => $child) {
            if (is_string($child)) {
                $children[$key] = $children[$key]::getNavigationItem();
            }
        }

        return new static($route, $routeData, $name, $icon, $children);
    }

    /**
     * Make divider (NavigationItemDivider static constructor)
     */
    public static function makeDivider(string $title, string $icon = ''): NavigationItemDivider
    {
        return new NavigationItemDivider($title, $icon);
    }

    /**
     * Make manageable models (NavigationItemsManageableModels static import)
     *
     * @return NavigationItem[]
     */
    public static function makeManageableModels(... $models): array
    {
        return NavigationItemsManageableModels::import($models);
    }

    /**
     * Has children
     */
    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    /**
     * Get Url
     */
    public function getUrl(): string
    {
        if (empty($this->route)) {
            return '#';
        }

        return route($this->route, $this->routeData ?? []);
    }

    /**
     * Is active
     */
    public function isActive(): bool
    {
        return WRLAHelper::isCurrentRouteWithParameters($this->route, $this->routeData ?? []);
    }

    /**
     * Is child active
     */
    public function isChildActive(): bool
    {
        foreach ($this->children as $child) {
            if ($child->isActive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Show on condition. If false will be: not shown and disabled
     *
     * @return $this
     */
    public function showOnCondition(callable $callback): static
    {
        $this->showOnConditionCallback = $callback;

        return $this;
    }

    /**
     * Check show condition
     */
    public function checkShowCondition(): bool
    {
        if ($this->showOnConditionCallback === null) {
            return true;
        }

        return call_user_func($this->showOnConditionCallback);
    }

    /**
     * Enable on condition, callback must return true or a string with the reason why not enabled (appears as tooltip)
     *
     * @return $this
     */
    public function enableOnCondition(callable $callback): static
    {
        $this->enableOnConditionCallback = $callback;

        return $this;
    }

    /**
     * Check enabled condition, returns true or a string with the reason why not enabled (appears as tooltip)
     */
    public function checkEnabledCondition(): true|string
    {
        if ($this->enableOnConditionCallback === null) {
            return true;
        }

        return call_user_func($this->enableOnConditionCallback);
    }

    /**
     * Open in new tab
     *
     * @return $this
     */
    public function openInNewTab(bool $openInNewTab = true): static
    {
        $this->openInNewTab = $openInNewTab;

        return $this;
    }

    /**
     * Render the navigation item
     */
    public function render(): string
    {
        return $this->name;
    }
}
