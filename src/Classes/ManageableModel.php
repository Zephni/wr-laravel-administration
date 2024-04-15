<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Support\Stringable;

class ManageableModel
{
    /**
     * Model that this manageable model is based on, eg. \App\Models\User::class.
     *
     * @var string|null
     */
    public static $baseModel = null;

    /**
     * Collection of manageable models, pushed to in the register method which is called within the serice provider.
     *
     * @var ?\Illuminate\Support\Collection
     */
    public static ?\Illuminate\Support\Collection $manageableModels = null;

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
     * Get manageable model by model class.
     *
     * @param string $modelClass
     * @return mixed
     */
    public static function getByModelClass(string $modelClass): mixed
    {
        return self::$manageableModels->first(function ($manageableModel) use ($modelClass) {
            return $manageableModel::$baseModel === $modelClass;
        });
    }

    /**
     * Get manageable model by URL alias.
     *
     * @param string $urlAlias
     * @return mixed
     */
    public static function getByUrlAlias(string $urlAlias): mixed
    {
        return self::$manageableModels->first(function ($manageableModel) use ($urlAlias) {
            return $manageableModel::getUrlAlias() === $urlAlias;
        });
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
