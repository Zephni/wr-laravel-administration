<?php

namespace WebRegulate\LaravelAdministration\Classes\VersionHandler;

/**
 * The outcome of an individual step within a version update.
 *
 * Version updates use this (via $context->change()) to clearly communicate to
 * the user whether a given step *actually changed something*, made no change at
 * all, only changed part of what it intended, or was skipped entirely.
 *
 * Adding a new outcome here is all that's required to support it everywhere —
 * the label, prefix and output severity are defined alongside each case, so the
 * context and summary logic never need to be touched again.
 */
enum VersionChangeStatus: string
{
    /** The step applied a change successfully. */
    case Changed = 'changed';

    /** Nothing needed doing — the target was already in the desired state. */
    case Unchanged = 'unchanged';

    /** Only some of the intended change could be applied. */
    case Partial = 'partial';

    /** The step could not run (eg. a required file was missing) — nothing done. */
    case Skipped = 'skipped';

    /**
     * Short human readable label for the outcome.
     */
    public function label(): string
    {
        return match ($this) {
            self::Changed => 'Changed',
            self::Unchanged => 'Unchanged',
            self::Partial => 'Partial',
            self::Skipped => 'Skipped',
        };
    }

    /**
     * The bracketed prefix shown before each change message,
     * eg. "[changed]" / "[unchanged]".
     */
    public function prefix(): string
    {
        return '[' . $this->value . ']';
    }

    /**
     * Which context output severity this outcome should be written at.
     * Maps to the line()/info()/warn()/error() methods on the context.
     */
    public function severity(): string
    {
        return match ($this) {
            self::Changed => 'info',
            self::Unchanged => 'line',
            self::Partial, self::Skipped => 'warn',
        };
    }
}
