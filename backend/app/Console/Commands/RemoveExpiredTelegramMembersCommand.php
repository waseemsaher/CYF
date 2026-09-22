<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Telegram\Actions\RemoveExpiredMembers;
use Illuminate\Console\Command;

class RemoveExpiredTelegramMembersCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'telegram:remove-expired';

    /**
     * @var string
     */
    protected $description = 'Remove users with expired or revoked enrollments from Telegram course groups';

    public function handle(RemoveExpiredMembers $action): int
    {
        $this->info('Checking for expired enrollments in Telegram groups...');

        $removed = $action->handle();

        $this->info("Completed. Removed {$removed} member(s).");

        return self::SUCCESS;
    }
}
