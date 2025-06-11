<?php

namespace WebRegulate\LaravelAdministration\Classes;

abstract class ConfiguredModeBasedHandler
{
    /**
     * Current mode
     */
    protected string $mode;

    /**
     * Mode configuration
     */
    protected array $currentConfiguration;

    /**
     * Base configuration path
     */
    abstract public function baseConfigurationPath(): string;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Get all configuration for the given base path
        $baseConfiguration = config('wr-laravel-administration.'.$this->baseConfigurationPath());

        // Get the current mode
        $this->mode = $baseConfiguration['current'] ?? null;

        // If mode is empty, it means we are not using this feature
        if (empty($this->mode)) return;

        // Get mode configuration
        $this->currentConfiguration = $baseConfiguration[$this->mode] ?? [];
    }
}