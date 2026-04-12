<?php

namespace App\Helpers;

use App\Models\ReferenceCount;
use Carbon\Carbon;

class Task
{
    public static function setAndGetReferenceCount($type, $business_id = 0)
    {
        $ref = ReferenceCount::where('ref_type', $type)
            ->first();
        if (!empty($ref)) {
            $ref->ref_count += 1;
            $ref->save();
            return $ref->ref_count;
        } else {
            $new_ref = ReferenceCount::create([
                'ref_type' => $type,
                'ref_count' => 1,
                'business_id' => $business_id
            ]);
            return $new_ref->ref_count;
        }
    }

    public static function generateReferenceNumber($type, $ref_count, $default_prefix = null)
    {
        $prefix = '';

        if (!empty($default_prefix)) {
            $prefix = $default_prefix;
        }

        $ref_digits = str_pad($ref_count, 6, 0, STR_PAD_LEFT);

        if (in_array($type, ['payment'])) {
            $ref_year = Carbon::now()->year;
            $ref_number = $prefix . $ref_year . '/' . $ref_digits;
        } else {
            $ref_number = $prefix . $ref_digits;
        }

        return $ref_number;
    }

    public static function WriteLog($message, $date = null, $author = null, $event = null)
    {
        $path = storage_path('/logs/website.log');

        if (file_exists($path)) {
            $f = fopen($path, "a+");
        } else {
            $f = fopen($path, "w+");
        }

        $created_at = now();

        if (isset($date) && $date) {
            $created_at = $date;
        }

        if (!$author) {
            $author = "system";
        }

        if (!$event) {
            $event = "log";
        }

        $content = "Ngày: " . $created_at . " | Người thực hiện: " . $author . " | Log: " . $event . " : " . $message;

        file_put_contents($path, $content . "\n", FILE_APPEND);

        fclose($f);
    }
}