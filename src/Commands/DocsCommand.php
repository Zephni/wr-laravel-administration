<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;

class DocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wrla:docs {--port=8888 : The port to serve the documentation on}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Serve the WRLA local documentation and open it in your browser.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $port = (int) $this->option('port');
        $docsPath = realpath(__DIR__ . '/../../docs');

        if (! $docsPath || ! is_dir($docsPath)) {
            $this->error('Documentation directory not found at: ' . __DIR__ . '/../../docs');
            return 1;
        }

        $url = "http://localhost:{$port}";

        $this->line('');
        $this->info(" WRLA Documentation");
        $this->line(" URL:  <href={$url}>{$url}</>");
        $this->line(" Path: {$docsPath}");
        $this->line('');
        $this->line(' Press <comment>Ctrl+C</comment> to stop the server.');
        $this->line('');

        // Open browser after a short delay to allow the server to bind
        $this->openBrowser($url);

        // Start PHP built-in server (blocking — exits when user presses Ctrl+C)
        passthru(sprintf('php -S localhost:%d -t %s', $port, escapeshellarg($docsPath)));

        return 0;
    }

    /**
     * Open the given URL in the default system browser.
     */
    protected function openBrowser(string $url): void
    {
        match (PHP_OS_FAMILY) {
            'Windows' => exec(sprintf('start "" %s', escapeshellarg($url))),
            'Darwin'  => exec(sprintf('open %s', escapeshellarg($url))),
            default   => exec(sprintf('xdg-open %s 2>/dev/null &', escapeshellarg($url))),
        };
    }
}
