<?php

namespace App\Http\Controllers\Common;

use App\Helpers\Activity;
use App\Models\ActivityHistory;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use App\Jobs\SendMailJob;

class MailController extends Controller
{

    public function sendMail(Request $request)
    {
        DB::beginTransaction();

        try {
            $messages = [
                'email.required' => 'Vui lòng nhập email',
                'email.email' => 'Nhập một email hợp lệ',
            ];

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ], $messages);

            if ($validator->fails()) {
                $message = "Dữ liệu không hợp lệ!";

                if ($validator->errors()->all()[0]) {
                    $message = $validator->errors()->all()[0];
                }

                return $this->respondWithError($message, $validator->errors()->all(), 422);
            }

            $mail = $request->email;

            $data = array(
                'mail' => $mail,
                'description' => isset($request->description) ? $request->description : "",
            );

            $files = isset($request->attachments) ? $request->attachments : [];

            $job = (new SendMailJob($mail, $data, $files));
            dispatch($job);

            $message = "Đã gửi mật khẩu về email " . $request->email;

            return $this->respondSuccess($message);
        } catch (\Exception $err) {
            return $this->respondWithError($err->getMessage(), [], 500);
        }
    }

    public function download(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("No permision!!", [], 401);
            }

            $user_id = Auth::guard('api')->user()->id;
            $username = Auth::guard('api')->user()->username;

            $media = Media::where("id", $id)->first();

            if (!$media) {
                return $this->respondWithError("Không tìm thấy file đính kèm", [], 500);
            }

            $media_path = public_path($media->file_path);
            $file_contents = base64_encode(file_get_contents($media_path));
            $src = 'data: ' . mime_content_type($media_path) . ';base64,' . $file_contents;

            Activity::activityLog($media->file_name, $request->object_id, "attachment", "download_file", $user_id, ["username" => $username, "object_type" => $request->object_type]);

            DB::commit();
            return $this->respondSuccess([
                'detail' => $media,
                'src' => $src
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("No permision!!", [], 401);
            }

            $input = $request->only(['file_name']);
            $user_id = Auth::guard('api')->user()->id;
            $username = Auth::guard('api')->user()->username;

            $media = Media::where("id", $id)->first();
            if (!$media) {
                return $this->respondWithError("Không tìm thấy tệp đính kèm", [], 500);
            }

            $msg = $media->file_name;

            if (isset($input['file_name'])) {
                $msg = $media->file_name  . " thành " . $input["file_name"];

                $media->file_name = $input['file_name'];
            }

            $media->save();
            Activity::activityLog($msg, $request->object_id, "attachment", "rename_file", $user_id, ["username" => $username, "object_type" => $request->object_type]);

            DB::commit();
            $message = "Cập nhật tệp đính kèm thành công";

            return $this->respondSuccess($media, $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }

    public function delete(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("No permision!!", [], 401);
            }
            $user_id = Auth::guard('api')->user()->id;
            $username = Auth::guard('api')->user()->username;

            $media = Media::findOrFail($id);

            $media_path = public_path($media->file_path);

            if (file_exists($media_path)) {
                File::delete($media_path);
            }

            $media->delete();

            Activity::activityLog($media->file_name, $request->object_id, "attachment", "delete_file", $user_id, ["username" => $username, "object_type" => $request->object_type]);

            DB::commit();
            $message = "Xoá tệp đính kèm thành công";

            return $this->respondSuccess([], $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            return $this->respondWithError($message, [], 500);
        }
    }
}
