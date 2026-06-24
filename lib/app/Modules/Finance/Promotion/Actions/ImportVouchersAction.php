<?php

namespace App\Modules\Finance\Promotion\Actions;

use App\Modules\Finance\Promotion\Services\VoucherImportService;

class ImportVouchersAction
{
    public function handle(...$params)
    {
        return app(VoucherImportService::class)->import(...$params);
    }
}
