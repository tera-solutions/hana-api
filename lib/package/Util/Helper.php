<?php

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/12/2020
 * Time: 2:45 PM
 */

namespace Package\Util;

use App\Models\Gallery;
use App\Models\ReferenceCount;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Image;

class Helper
{
    public static function uploadAndSaveFile($file, $data = null, $author = "system", $folder = null)
    {
        try {
            $gallery_data = [];

            $title = isset($data->title) ? $data->title : null;
            $business_id = isset($data->business_id) ? $data->business_id : null;
            $location_id = isset($data->location_id) ? $data->location_id : null;
            $created_by = isset($data->created_by) ? $data->created_by : null;

            if (isset($file) && $file) {
                $filename = null;
                if (isset($title)) {
                    $filename = str_replace(' ', '-', $title);
                }

                $files_result = Helper::UploadResource($file, $filename, $folder);

                if ($files_result == false) {
                    $response = [
                        'status' => false,
                        'msg' =>  'Không thể tải lên tệp tin!',
                    ];

                    return $response;
                }

                if (!isset($title)) {
                    $filename = isset($files_result['file_name']) ? now()->timestamp . '_' . $files_result['file_name'] : now()->timestamp;
                }

                $gallery_data = [
                    'file_path' => isset($files_result['path']) ? $files_result['path'] : "",
                    'file_thumb' => isset($files_result['thumb']) ? $files_result['thumb'] : "",
                    'file_title' => $filename,
                    'file_alt' => isset($data->file_alt) ? $data->file_alt : "",
                    'file_description' => isset($data->file_description) ? $data->file_description : "",
                    'file_author' => $author,
                    'file_size' => isset($files_result['size']) ? $files_result['size'] : 0,
                    'file_type' => isset($files_result['type']) ? $files_result['type'] : "other",
                    'created_by' => $created_by,
                    'created_at' => now()
                ];

                $save_result = Gallery::updateOrCreate($gallery_data);

                if (!$save_result) {

                    $image_path = public_path($gallery_data['file_path']);
                    if (File::exists($image_path)) {
                        File::delete($image_path);
                    }

                    $image_thumb = public_path($gallery_data['file_thumb']);
                    if (File::exists($image_thumb)) {
                        File::delete($image_thumb);
                    }

                    $response = [
                        'status' => false,
                        'msg' =>  'không thể tải lên tệp tin',
                    ];

                    return $response;
                }

                if (isset($save_result->id) && $save_result->id) {
                    $gallery_data['id'] = $save_result->id;
                }
            } else {
                $response = [
                    'status' => false,
                    'msg' =>  'Không có tệp tin nào được chọn để tải lên',
                ];

                return $response;
            }

            $gallery_data['status'] = true;

            return $gallery_data;
        } catch (\Exception $e) {
            $message = 'Lỗi hệ thống';

            if (env('APP_DEBUG') == true) {
                $message = $e->getMessage();
            }

            $response = [
                'status' => false,
                'msg' =>  $message,
            ];

            return $response;
        }
    }

    public static function UploadResource($resource, $title = null, $folder = "")
    {
        try {
            $fileName = $title;

            $type = $resource->getClientMimeType();
            $size = $resource->getSize();
            $name = $resource->getClientOriginalName();
            $extend = $resource->getClientOriginalExtension();

            $path_image = 'assets/upload';

            if (isset($folder) && $folder) {
                $path_image = 'assets/upload/' . $folder;
            }

            $pathSave = public_path($path_image);

            $originalName =  Str::of($name)->basename('.' . $extend);

            if (!isset($title)) {
                $fileName = now()->timestamp . '_' . Str::slug($originalName, '-');
                $fileName = str_replace('(', '', $fileName);
                $fileName = str_replace(')', '', $fileName);
                $fileName = Str::lower($fileName) . '.' . $resource->getClientOriginalExtension();
                $title = $name;
            } else {
                $fileName = now()->timestamp . '_' . $fileName . '.' . $resource->getClientOriginalExtension();
            }

            $resource->move($pathSave, $fileName);

            $result = [
                'path' => $path_image . '/' . $fileName,
                'thumb' => $path_image . '/' . $fileName,
                'file_name' => $title,
                'size' => $size,
                'type' => $type
            ];

            return $result;
        } catch (\Throwable  $e) {
            throw new \Exception($e);
        }
    }

    public static function formatBytes($size, $precision = 2)
    {
        if ($size > 0) {
            $size = (int) $size;
            $base = log($size) / log(1024);
            $suffixes = array(' bytes', ' KB', ' MB', ' GB', ' TB');

            return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
        } else {
            return $size;
        }
    }

    public static function convertBytes($size, $precision = 2)
    {
        if ($size > 0) {
            $size = (int) $size;
            return round($size / 1048576, $precision);
        } else {
            return 0;
        }
    }

    public static function setAndGetReferenceCount($type)
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
                'ref_count' => 1
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

        $ref_digits =  str_pad($ref_count, 4, 0, STR_PAD_LEFT);

        if (!in_array($type, ['payment', "user"])) {
            $ref_year = Carbon::now()->year;
            $ref_number = $prefix . $ref_year . '/' . $ref_digits;
        } else {
            $ref_number = $prefix . $ref_digits;
        }

        return $ref_number;
    }

    public static function WriteLog($file, $content)
    {
        $f = fopen($file, "a+");
        file_put_contents($file, $content, FILE_APPEND);
        fclose($f);
    }
}
