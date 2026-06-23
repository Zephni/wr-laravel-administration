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
}
