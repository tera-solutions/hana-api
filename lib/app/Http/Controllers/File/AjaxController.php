<?php

namespace App\Http\Controllers\File;

use App\Helpers\Activity;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Util;
use Illuminate\Support\Facades\File;
use Excel;
use Package\Exception\HttpException;

class AjaxController extends Controller
{

    public function upload(Request $request)
    {
        DB::beginTransaction();
        $resultFile = "";

        try {
            // if (!Auth::guard('api')->user()) {
            //     return $this->respondWithError("No permision!!", [], 401);
            // }

            $user_id = Auth::guard('api')->user()->id ?? null;
            $username = Auth::guard('api')->user()->username ?? null;

            $device_code = $request->header("device-code");

            if (!$device_code) {
                return $this->respondWithError("Thiết bị bị từ chối!", [], 500);
            }

            $input = $request->only([
                'app_id',
                'upload_time',
                'secure_code',
                'file',
                'title',
                'description'
            ]);


            $request->validate([
                'file' => 'required|mimes:jpg,jpge,png,svg,gif,gifv,pdf,doc,docx,xlsx,xltx,zip,csv,txt,avi,mov,webm,mp4,m4p,mpg,mp2,mpeg,mpe,mpv,m4v,svi,apk,psd,ai,sql,rar,xls,ppt,pptx,html',
                'app_id' => 'required',
                'secure_code' => 'required',
            ]);

            if ($request->hasFile('file')) {
                $fileSizeInBytes = $request->file('file')->getSize();
                $maxFileSizeInBytes = 10 * 1024 * 1024; // 10MB

                if ($fileSizeInBytes > $maxFileSizeInBytes) {
                    DB::rollBack();
                    $message = "File tải lên không được vượt quá 10MB.";
                    return $this->respondWithError($message, [], 500);
                }
            }

            if ($request->app_id != 2) {
                DB::rollBack();
                $message = "Tải file lên không thành công! Thiết bị bị từ chối";

                return $this->respondWithError($message, [], 500);
            }

            $file = $request->file('file');

            $title = null;

            if (!empty($request->title)) {
                $title = $request->title;
            }

            $folder = "";

            if (!empty($request->folder)) {
                $folder = $request->folder;
            }


            if (!$file) {
                DB::rollBack();
                $message = "Tải file lên không thành công! Thiết bị bị từ chối";

                return $this->respondWithError($message, [], 500);
            }

            $resultFile = Util::UploadResource($file, $title, $folder);

            $res = [
                "message" => "Tải file lên hệ thống thành công"
            ];

            if (isset($resultFile['path']) && $resultFile['path']) {
                $res['image'] = url($resultFile['path']);
            }

            if (isset($resultFile['thumb']) && $resultFile['thumb']) {
                $res['thumb'] = url($resultFile['thumb']);
            }
            $type = !empty($request->object_key) ? $request->object_key : "attachment";

            $dataUpdate = [
                'file_path' => $resultFile['path'],
                'file_name' =>  $resultFile['file_name'],
                'file_type' =>  $resultFile['type'],
                'file_size' =>  $resultFile['size'],
                'object_id' => !empty($request->object_id) ? $request->object_id : "",
                'object_type' => $type,
                'title' => $request->title,
                'description' => $request->description,
                'uploaded_by' => $user_id,
                'type' => $request->type,
            ];

            $result = Media::create($dataUpdate);

            if (!$result) {
                File::delete(public_path($resultFile['path']));
                return $this->respondWithError("Lỗi trong quá trình tải file", [], 500);
            }

            $res["id"] = $result->id;

            Activity::activityLog($resultFile['file_name'], $request->object_id, $type, "uploaded", $user_id, ["username" => $username, "image_id" => $result->id]);

            DB::commit();
            return $this->respondSuccess($res);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            if (isset($resultFile['path']) &&  File::exists(public_path($resultFile['path']))) {
                File::delete(public_path($resultFile['path']));
            }
            return $this->respondWithError($message, [], 500);
        }
    }

    public function ckeditorUpload(Request $request)
    {
        DB::beginTransaction();
        $resultFile = null;
        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("No permision!!", [], 401);
            }

            $user_id = Auth::guard('api')->user()->id;

            $input = $request->only([
                'ckCsrfToken',
                'upload',
            ]);


            $request->validate([
                'upload' => 'required',
                'ckCsrfToken' => 'required',
            ]);

            $file = $request->file('upload');


            if (!$file) {
                DB::rollBack();
                $message = "Tải file lên không thành công! Thiết bị bị từ chối";

                return $this->respondWithError($message, [], 500);
            }

            $title = null;

            if (!empty($request->title)) {
                $title = $request->title;
            }

            $folder = "";

            if (!empty($request->folder)) {
                $folder = $request->folder;
            }

            $resultFile = Util::UploadResource($file, $title, $folder);

            $res  = [];

            if (isset($resultFile['path']) && $resultFile['path']) {
                $res['default'] = url($resultFile['path']);
            }

            if (isset($resultFile['thumb']) && $resultFile['thumb']) {
                $res['900'] = url($resultFile['thumb']);
            }

            $dataUpdate = [
                'file_path' => $resultFile['path'],
                'file_name' =>  $resultFile['file_name'],
                'file_type' =>  $resultFile['type'],
                'file_size' =>  $resultFile['size'],
                'model_id' => !empty($request->model_id) ? $request->model_id : "",
                'uploaded_by' => $user_id,
                'type' => $request->type,
            ];

            $result = Media::create($dataUpdate);

            if (!$result) {
                File::delete(public_path($resultFile['path']));
                return $this->respondWithError("Lỗi trong quá trình tải file", [], 500);
            }

            DB::commit();
            return $this->respondSuccess($res);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            if (isset($resultFile['path']) &&  File::exists(public_path($resultFile['path']))) {
                File::delete(public_path($resultFile['path']));
            }
            return $this->respondWithError($message, [], 500);
        }
    }

    public function importFile(Request $request)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("No permision!!", [], 401);
            }

            $user_id = Auth::guard('api')->user()->id;
            $username = Auth::guard('api')->user()->username;

            $device_code = $request->header("device-code");

            if (!$device_code) {
                return $this->respondWithError("Thiết bị bị từ chối!", [], 500);
            }

            $request->validate([
                'file' => 'required|mimes:xlsx, csv, xls',
                'app_id' => 'required',
                'secure_code' => 'required',
            ]);

            if ($request->app_id != 2) {
                DB::rollBack();
                $message = "Tải file lên không thành công! Thiết bị bị từ chối";

                return $this->respondWithError($message, [], 500);
            }

            $file = $request->file('file');
            $parsed_array = Excel::toArray([], $file);

            //Remove header row
            $imported_data = array_splice($parsed_array[0], 1);

            return $this->respondSuccess($imported_data);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->respondWithError($message, [], 500);
        }
    }

    public function download(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user_id = Auth::guard('api')->user()->id;
            $username = Auth::guard('api')->user()->username;

            $media = Media::where("id", $id)->first();
            if (!$media) {
                throw new HttpException('Không tìm thấy tập tin đính kèm', 404);
            }

            $media_path = public_path($media->file_path);
            $file_contents = base64_encode(file_get_contents($media_path));
            $src = 'data: ' . mime_content_type($media_path) . ';base64,' . $file_contents;

            Activity::activityLog($media->file_name, $request->object_id, "attachment", "download_file", $user_id, ["username" => $username, "object_type" => $request->object_type]);

            $data = [
                'detail' => $media,
                'src' => $src
            ];

            DB::commit();
            return $data;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new HttpException($message);
        }
    }
}
