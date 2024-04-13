<?php

namespace WebRegulate\LaravelAdministration\Classes;

class ManageableModel
{
    // Built in service provider
    private static $manageableModels = null;

    public static function register(): void
    {
        // If manageable models is null, set it to a collection
        if(is_null(self::$manageableModels)) {
            self::$manageableModels = collect();
        }

        // Register the model
        self::$manageableModels->push(static::class);
    }

    public static function getUrlAlias(): string
    {
        return 'manageable-model';
    }

    public static function getDisplayName(): string
    {
        return 'Manageable Model';
    }

    public static function getIcon(): string
    {
        return 'fa fa-question';
    }
}
