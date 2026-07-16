<?php

namespace App\Console\Commands;

use App\Modules\System\Subscription\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark active subscriptions past their expiry date as expired.';

    public function handle(): int
    {
        $count = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now()->toDateString())
            ->update(['status' => Subscription::STATUS_EXPIRED]);

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
