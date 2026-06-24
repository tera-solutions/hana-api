<?php

namespace App\Modules\Finance\Promotion\Services;

use App\Models\Media;
use App\Modules\Finance\Promotion\Models\Promotion;
use App\Modules\Finance\Promotion\Models\Voucher;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import vouchers for a promotion from an uploaded spreadsheet. The file is referenced by
 * an uploaded media record (file_id); the columns follow the template:
 * Voucher Code | Usage Limit | Expired At.
 * Valid rows are persisted; per-row errors are reported without rolling back.
 */
class VoucherImportService
{
    /**
     * @param  array<string, mixed>  $data  file_id + optional defaults
     * @return array{imported: int, failed: array<int, array{row: int, errors: array<string, array<int, string>>}>}
     *
     * @throws \RuntimeException
     */
    public function import($promotionId, array $data): array
    {
        $promotion = Promotion::findOrFail($promotionId);

        $rows = $this->readRows($data['file_id']);
        $header = $this->headerMap(array_shift($rows));

        $defaults = [
            'usage_limit' => max(1, (int) ($data['default_usage_limit'] ?? 1)),
            'expired_at' => $data['default_expired_at'] ?? $promotion->end_date?->endOfDay(),
        ];

        $imported = 0;
        $failed = [];
        $seen = [];

        foreach ($rows as $index => $row) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $payload = $this->rowToPayload($row, $header, $defaults, $promotion->id);
            $rowNumber = $index + 2; // 1-based, past the header

            if ($errors = $this->validateRow($payload, $seen)) {
                $failed[] = ['row' => $rowNumber, 'errors' => $errors];

                continue;
            }

            $seen[] = $payload['voucher_code'];

            try {
                Voucher::create([
                    'promotion_id' => $payload['promotion_id'],
                    'voucher_code' => $payload['voucher_code'],
                    'usage_limit' => $payload['usage_limit'],
                    'used_count' => 0,
                    'expired_at' => $payload['expired_at'],
                    'status' => Voucher::STATUS_ACTIVE,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $failed[] = ['row' => $rowNumber, 'errors' => ['import' => [$e->getMessage()]]];
            }
        }

        return ['imported' => $imported, 'failed' => $failed];
    }

    /**
     * Read the first worksheet of the media file as an array of rows.
     *
     * @return array<int, array<int, mixed>>
     *
     * @throws \RuntimeException
     */
    private function readRows($fileId): array
    {
        $media = Media::find($fileId);

        if (! $media) {
            throw new \RuntimeException('Không tìm thấy tập tin import.');
        }

        $path = public_path($media->file_path);

        if (! File::exists($path)) {
            throw new \RuntimeException('Tập tin import không tồn tại trên hệ thống.');
        }

        $rows = Excel::toArray([], $path)[0] ?? [];

        if (count($rows) < 2) {
            throw new \RuntimeException('Tập tin import không có dữ liệu.');
        }

        return $rows;
    }

    /**
     * Map (lower-cased, trimmed) header labels to their column index.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, int>
     */
    private function headerMap(array $row): array
    {
        $map = [];

        foreach ($row as $i => $label) {
            $map[strtolower(trim((string) $label))] = $i;
        }

        return $map;
    }

    /**
     * Build a create payload from a spreadsheet row.
     *
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $header
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function rowToPayload(array $row, array $header, array $defaults, $promotionId): array
    {
        $cell = fn (string $name): string => isset($header[$name]) ? trim((string) ($row[$header[$name]] ?? '')) : '';

        $usageLimit = $cell('usage limit');
        $expiredAt = $cell('expired at');

        return [
            'promotion_id' => $promotionId,
            'voucher_code' => strtoupper($cell('voucher code')),
            'usage_limit' => $usageLimit !== '' ? (int) $usageLimit : $defaults['usage_limit'],
            'expired_at' => $expiredAt !== '' ? $expiredAt : $defaults['expired_at'],
        ];
    }

    /**
     * Validate a parsed row, returning field => messages (empty when valid).
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $seen  codes already accepted in this import
     * @return array<string, array<int, string>>
     */
    private function validateRow(array $payload, array $seen): array
    {
        $validator = Validator::make($payload, [
            'voucher_code' => ['required', 'string', 'max:50', 'unique:fin_vouchers,voucher_code'],
            'usage_limit' => ['required', 'integer', 'min:1'],
            'expired_at' => ['nullable', 'date'],
        ], [
            'voucher_code.required' => 'Mã voucher là bắt buộc.',
            'voucher_code.unique' => 'Mã voucher đã tồn tại.',
        ]);

        $validator->after(function ($validator) use ($payload, $seen) {
            if (in_array($payload['voucher_code'], $seen, true)) {
                $validator->errors()->add('voucher_code', 'Mã voucher bị trùng trong tập tin.');
            }
        });

        return $validator->errors()->toArray();
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn ($v) => trim((string) $v) === '');
    }
}
