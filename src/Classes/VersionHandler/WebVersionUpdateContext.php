<?php

namespace WebRegulate\LaravelAdministration\Classes\VersionHandler;

use Closure;
use Symfony\Component\Process\Process;

/**
 * Web (dev-tools modal) implementation of the version update context.
 *
 * Livewire cannot block on interactive prompts mid-request, so output is
 * buffered into a string (optionally pushed live via an output callback) and
 * questions are resolved from pre-supplied answers, falling back to the
 * provided default. A version update that needs a genuine decision in the web
 * context should therefore expose it via config rather than a blocking prompt.
 */
class WebVersionUpdateContext extends VersionUpdateContext
{
    /**
     * Accumulated console-style output.
     */
    protected string $output = '';

    /**
     * Optional callback invoked whenever new output is appended.
     *
     * @var (Closure(string): void)|null
     */
    protected ?Closure $onOutput;

    /**
     * Pre-supplied answers keyed by the question key.
     *
     * @param array<string, mixed> $answers
     * @param (Closure(string): void)|null $onOutput
     */
    public function __construct(protected array $answers = [], ?Closure $onOutput = null)
    {
        $this->onOutput = $onOutput;
    }

    /**
     * Get the full buffered output.
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    protected function append(string $message): void
    {
        $this->output .= $message;

        if ($this->onOutput !== null) {
            ($this->onOutput)($this->output);
        }
    }

    public function line(string $message = ''): void
    {
        $this->append($message . PHP_EOL);
    }

    public function info(string $message): void
    {
        $this->append($message . PHP_EOL);
    }

    public function warn(string $message): void
    {
        $this->append('[warning] ' . $message . PHP_EOL);
    }

    public function error(string $message): void
    {
        $this->append('[error] ' . $message . PHP_EOL);
    }

    public function ask(string $key, string $question, ?string $default = null): ?string
    {
        return array_key_exists($key, $this->answers) ? (string) $this->answers[$key] : $default;
    }

    public function confirm(string $key, string $question, bool $default = false): bool
    {
        return array_key_exists($key, $this->answers) ? (bool) $this->answers[$key] : $default;
    }

    public function choice(string $key, string $question, array $choices, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->answers) ? $this->answers[$key] : $default;
    }

    public function runProcess(array $command, ?string $workingDirectory = null, int $timeout = 600): bool
    {
        $process = new Process($command, $workingDirectory ?? base_path());
        $process->setTimeout($timeout);

        $process->run(function ($type, $buffer) {
            $this->append($buffer);
        });

        return $process->isSuccessful();
    }
}
