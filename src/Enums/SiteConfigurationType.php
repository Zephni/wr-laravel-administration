<?php

namespace WebRegulate\LaravelAdministration\Enums;

/**
 * The set of value types a site configuration item can hold.
 *
 * The backing value of each case is what gets stored in the
 * `wrla_site_configurations.type` column. The {@see \App\Models\SiteConfiguration::getValue()}
 * method uses these cases to decide how the raw stored value is cast/decoded
 * before it is returned to the application.
 */
enum SiteConfigurationType: string
{
    /** Plain text, returned exactly as stored. */
    case Text = 'text';

    /** A JSON string, returned as a decoded associative array. */
    case Json = 'json';

    /** A comma separated list, returned as an array of trimmed, non-empty strings. */
    case CommaDelimitedArray = 'comma_delimited_array';

    /** A truthy/falsy value, returned as a real boolean. */
    case Boolean = 'boolean';

    /** An image stored on disk, returned as a base64 encoded data URI. */
    case Image = 'image';

    /** A base64 encoded string, returned decoded to its original form. */
    case Base64 = 'base64';

    /**
     * Short human readable label for this type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Json => 'JSON',
            self::CommaDelimitedArray => 'Comma delimited array',
            self::Boolean => 'Boolean',
            self::Image => 'Image',
            self::Base64 => 'Base64',
        };
    }

    /**
     * Help text explaining how the value is stored and what getValue() returns.
     */
    public function description(): string
    {
        return match ($this) {
            self::Text => 'Plain text. Stored and returned exactly as entered.',
            self::Json => 'A JSON string. Stored as raw JSON, returned json_decoded into an associative array.',
            self::CommaDelimitedArray => 'A comma separated list (e.g. "one, two, three"). Returned as an array of trimmed values.',
            self::Boolean => 'A true/false toggle. Returned as a real boolean.',
            self::Image => 'An uploaded image. Stored on disk and returned as a base64 encoded data URI (data:image/...).',
            self::Base64 => 'A base64 encoded string. Stored encoded, returned base64_decoded to its original value.',
        };
    }

    /**
     * Options array suitable for a Select field ([value => label]).
     */
    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /**
     * Combined help text for every type, suitable for field notes.
     */
    public static function helpText(): string
    {
        return collect(self::cases())
            ->map(fn (self $case) => "{$case->label()}: {$case->description()}")
            ->implode(' ');
    }
}
