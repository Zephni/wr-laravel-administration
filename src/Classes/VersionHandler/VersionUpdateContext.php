<?php

namespace WebRegulate\LaravelAdministration\Classes\VersionHandler;

/**
 * Abstracts input/output for a version update so the same VersionUpdate class
 * can run both from the console (interactive) and the web dev-tools modal
 * (buffered, non-blocking).
 *
 * Console implementation proxies to the artisan Command, while the web
 * implementation buffers output and resolves questions from pre-supplied
 * answers (falling back to defaults).
 */
abstract class VersionUpdateContext
{
    /**
     * Outcomes recorded via change(), used to build a per-version summary.
     *
     * @var array<int, array{status: VersionChangeStatus, message: string}>
     */
    protected array $changeLog = [];

    /**
     * Write a plain line of output.
     */
    abstract public function line(string $message = ''): void;

    /**
     * Write an informational (success styled) line of output.
     */
    abstract public function info(string $message): void;

    /**
     * Write a warning line of output.
     */
    abstract public function warn(string $message): void;

    /**
     * Write an error line of output.
     */
    abstract public function error(string $message): void;

    /**
     * Ask the user a free text question, returning their answer (or the default).
     */
    abstract public function ask(string $key, string $question, ?string $default = null): ?string;

    /**
     * Ask the user a yes/no question, returning a boolean.
     */
    abstract public function confirm(string $key, string $question, bool $default = false): bool;

    /**
     * Ask the user to pick from a list of choices, returning the selected value.
     *
     * @param array<int|string, string> $choices
     */
    abstract public function choice(string $key, string $question, array $choices, mixed $default = null): mixed;

    /**
     * Run a shell command, streaming/capturing its output. Returns true on success.
     *
     * @param array<int, string> $command
     */
    abstract public function runProcess(array $command, ?string $workingDirectory = null, int $timeout = 600): bool;

    /**
     * Report the outcome of a single step, explaining to the user whether it
     * actually changed something, made no change, only partially changed, or
     * was skipped.
     *
     * The message is written at the outcome's configured severity (so it's
     * styled consistently in both the console and the web modal) and recorded
     * so a summary can be shown once the version finishes.
     *
     * Example:
     *   $context->change(VersionChangeStatus::Changed, 'Developer config migrated.');
     *   $context->change(VersionChangeStatus::Unchanged, 'Developer config already present.');
     */
    public function change(VersionChangeStatus $status, string $message): void
    {
        $line = $status->prefix() . ' ' . $message;

        match ($status->severity()) {
            'info' => $this->info($line),
            'warn' => $this->warn($line),
            'error' => $this->error($line),
            default => $this->line($line),
        };

        $this->changeLog[] = ['status' => $status, 'message' => $message];
    }

    /**
     * The change outcomes recorded so far (since the last reset).
     *
     * @return array<int, array{status: VersionChangeStatus, message: string}>
     */
    public function recordedChanges(): array
    {
        return $this->changeLog;
    }

    /**
     * The recorded change outcomes as a numerically indexed array of human
     * readable lines, eg. ["[changed] Developer config migrated.", ...].
     * Suitable for logging every part of a version update in a single entry.
     *
     * @return array<int, string>
     */
    public function recordedChangeLines(): array
    {
        return array_values(array_map(
            fn(array $entry) => $entry['status']->prefix() . ' ' . $entry['message'],
            $this->changeLog
        ));
    }

    /**
     * Clear the recorded change log (called between versions so each version's
     * summary only reflects its own steps).
     */
    public function clearChangeLog(): void
    {
        $this->changeLog = [];
    }

    /**
     * Build a one-line summary of the recorded outcomes, eg.
     * "Changed: 1, Unchanged: 2". Returns null when nothing was recorded.
     */
    public function changeSummaryLine(): ?string
    {
        if ($this->changeLog === []) {
            return null;
        }

        $counts = [];

        foreach ($this->changeLog as $entry) {
            $label = $entry['status']->label();
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        $parts = [];
        foreach ($counts as $label => $count) {
            $parts[] = "{$label}: {$count}";
        }

        return implode(', ', $parts);
    }
}
