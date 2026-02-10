<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendTaskRemindersCommand extends Command
{
    protected $signature = 'bow:send-task-reminders';

    protected $description = 'Send task deadline reminders (stub - extend when notification system is ready)';

    public function handle(): int
    {
        $this->info('Task reminders: not yet implemented (no notification channel).');

        return Command::SUCCESS;
    }
}
