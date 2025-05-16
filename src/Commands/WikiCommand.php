<?php

namespace WebRegulate\LaravelAdministration\Commands;

use Illuminate\Console\Command;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class WikiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wrla:wiki';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display a link to the WRLA wiki.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Show link to documentation
        $this->line('');
        $this->alert('WRLA Documentation: '.WRLAHelper::getDocumentationUrl());

        return 1;
    }
}
