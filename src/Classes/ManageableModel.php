<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Support\Stringable;

class ManageableModel
{
    /**
     * Collection of manageable models, pushed to in the register method which is called within the serice provider.
     *
     * @var \Illuminate\Support\Collection|null
     */
    private static $manageableModels = null;

    /**
     * Register the manageable model.
     *
     * @return void
     */
    public static function register(): void
    {
        // If manageable models is null, set it to a collection
        if (is_null(self::$manageableModels)) {
            self::$manageableModels = collect();
        }

        // Register the model
        self::$manageableModels->push(static::class);
    }

    /**
     * Get the URL alias for the manageable model.
     *
     * @return string
     */
    public static function getUrlAlias(): string
    {
        return 'manageable-model';
    }

    /**
     * Get the display name for the manageable model.
     *
     * @return string
     */
    public static function getDisplayName(): Stringable
    {
        return str('Manageable Model');
    }

    /**
     * Get the icon for the manageable model.
     *
     * @return string
     */
    public static function getIcon(): string
    {
        return 'fa fa-question';
    }
}
