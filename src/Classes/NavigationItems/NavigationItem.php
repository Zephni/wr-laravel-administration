<?php

namespace WebRegulate\LaravelAdministration\Classes\NavigationItems;

use WebRegulate\LaravelAdministration\Classes\WRLAHelper;
use WebRegulate\LaravelAdministration\Enums\PageType;

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
     *
     * @param ?string $route
     * @param ?array $routeData
     * @param string $name
     * @param string $icon
     * @param array $children
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
    public function getUrl(): string
    {
        if(empty($this->route)) {
            return '#';
        }

        return route($this->route, $this->routeData ?? []);
    }

    /**
     * Is active
     * @return bool
     */
    public function isActive(): bool
    {
        return WRLAHelper::isCurrentRouteWithParameters($this->route, $this->routeData ?? []);
    }

    /**
     * Is child active
     * @return bool
     */
    public function isChildActive(): bool
    {
        foreach($this->children as $child) {
            if($child->isActive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Show on condition
     *
     * @param callable $callback
     * @return $this
     */
    public function showOnCondition(callable $callback): static
    {
        $this->showOnConditionCallback = $callback;
        return $this;
    }

    /**
     * Check show condition
     *
     * @return bool
     */
    public function checkShowCondition(): bool
    {
        if($this->showOnConditionCallback === null) {
            return true;
        }

        return call_user_func($this->showOnConditionCallback);
    }

    /**
     * Enable on condition, callback must return true or a string with the reason why not enabled (appears as tooltip)
     *
     * @param callable $callback
     * @return $this
     */
    public function enableOnCondition(callable $callback): static
    {
        $this->enableOnConditionCallback = $callback;
        return $this;
    }

    /**
     * Check enabled condition, returns true or a string with the reason why not enabled (appears as tooltip)
     *
     * @return true|string
     */
    public function checkEnabledCondition(): true|string
    {
        if($this->enableOnConditionCallback === null) {
            return true;
        }

        return call_user_func($this->enableOnConditionCallback);
    }

    /**
     * Open in new tab
     *
     * @param bool $openInNewTab
     * @return $this
     */
    public function openInNewTab(bool $openInNewTab = true): static
    {
        $this->openInNewTab = $openInNewTab;
        return $this;
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
