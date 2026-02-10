<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RecalculateDashboardCommand extends Command
{
    protected $signature = 'bow:recalculate-dashboard';

    protected $description = 'Recalculate dashboard statistics cache (stub - extend if caching is added)';

    public function handle(): int
    {
        $this->info('Dashboard recalculate: no cache layer yet, stats are computed on demand.');

        return Command::SUCCESS;
    }
}
