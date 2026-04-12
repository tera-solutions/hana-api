<?php

namespace App\Module\Portal\Repository;

use App\Helpers\Activity;
use App\Jobs\SendMailJob;
use App\Module\Portal\Model\ActivityLog;
use App\Module\Portal\Model\Media;
use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Package\Exception\DatabaseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Package\Exception\HttpException;
use App\Module\Portal\Model\Comment;
use App\Module\Portal\Model\Transaction;
use Carbon\Carbon;

class AttachmentRepository extends BasicEntity implements RepositoryInterface
{
    public $table;

    public $primaryKey;

    public $fillable;

    public $hidden;

    public function __construct()
    {
        $activityLog = new Media();
        $this->table =  $activityLog->getTable();
        $this->fillable =  $activityLog->getFillable();
        $this->hidden =  $activityLog->getHidden();
        $this->primaryKey =  $activityLog->getKeyName();
        array_push($this->fillable, $this->primaryKey);
    }

    public function all($request)
    {
        try {
            $arrayType = [
                "image" => [
                    "image/jpeg",
                    "image/png",
                    "image/gif",
                    "image/svg+xml"
                ],
                "video" => [
                    "video/mp4"
                ],
                "document" => [
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                    "application/pdf",
                    "application/octet-stream",
                    "application/json"
                ],
                "compressed" => [
                    "application/zip",
                    "application/x-rar-compressed"
                ]
            ];
            $activity = Media::with(["created_by"])
                ->leftJoin("users", "users.id", "media.uploaded_by")
                ->select([
                    "media.*"
                ]);

            if (isset($request->username) && $request->username) {
                $activity->where("users.username", 'LIke', "%$request->username%");
            }


            if (isset($request->uploaded_by) && $request->uploaded_by) {
                $activity->where("media.uploaded_by", 'LIke', "%$request->uploaded_by%");
            }

            if (isset($request->keyword) && $request->keyword) {
                $activity->where("media.file_name", 'LIke', "%$request->keyword%");
            }

            if (isset($request->object_id) && $request->object_id) {
                $activity->where("media.object_id", $request->object_id);
            }

            if (isset($request->object_type) && $request->object_type) {
                $activity->where("media.object_type", $request->object_type);
            }

            if (isset($request->file_type) && $request->file_type) {
                $type = $request->file_type;
                if (isset($arrayType[$type])) {
                    $activity->whereIn("media.file_type", $arrayType[$type]);
                }
            }

            if (isset($request->file_name) && $request->file_name) {
                $activity->where("media.file_name", "LIKE", "%$request->file_name%");
                $activity->where(function ($query) use ($request) {
                    $query->where("media.file_name", "LIKE", "%$request->file_name%");
                    $query->orWhere("media.object_type", "LIKE", "%$request->file_name%");
                });
            }

            $start_date = Carbon::now();

            if (!empty($request->start_date)) {
                $start_date = Carbon::createFromFormat('d/m/Y H:i:s', $request->start_date);
                $activity->whereDate('media.created_at', '>=', $start_date);
            }

            if (!empty($request->end_date)) {
                $end_date = Carbon::createFromFormat('d/m/Y H:i:s', $request->end_date);
                $activity->whereDate('media.created_at', '<=', $end_date);
            }

            $activity->orderBy('media.created_at', "desc");

            $data = $activity->paginate($request->limit);

            return $data;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new HttpException($message);
        }
    }

    public function find($id)
    {
        DB::beginTransaction();
        try {
            $media = Media::where("id", $id)->first();
            if (!$media) {
                throw new HttpException("không tìm thấy file đính kèm");
            }

            $media_path = public_path($media->file_path);

            if (!file_exists($media_path)) {
                $data = [
                    'detail' => $media,
                    'src' => null
                ];
                return $data;
            }

            $file_contents = base64_encode(file_get_contents($media_path));
            $src = 'data: ' . mime_content_type($media_path) . ';base64,' . $file_contents;

            $data = [
                'detail' => $media,
                'src' => $src
            ];

            return $data;
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            throw new HttpException($message);
        }
    }

    public function create($request)
    {
        DB::beginTransaction();
        try {
            $user_id = Auth::guard('api')->user()->id;
            $username = Auth::guard('api')->user()->username;
            $comment = Comment::create($request);

            if (!$comment) {
                DB::rollBack();
                throw new DatabaseException("Lỗi khi tạo bình luận", 500);
            }

            if (!empty($request->media_id) && !empty($request->object_id)) {
                $updateMedia = [
                    "object_id" => $request->object_id,
                ];

                if (!empty($request->object_type)) {
                    $updateMedia['object_type'] = $request->object_type;
                }

                Media::where("id", $request->media_id)->update($updateMedia);
            }

            if ($request->object_type === "order") {
                Transaction::where("id", $request->object_id)->update([
                    "view_status" => "new",
                    "updated_at" => now()
                ]);
            }

            Activity::activityLog($request->file_name, $request['object_id'] ?? null, "attachment", "created_file", $user_id, ["username" => $username, "object_type" => $request['object_type'] ?? null]);
            DB::commit();

            return $comment;
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();

            throw new HttpException($message);
        }
    }
    public function createManyOfRow($data)
    {
    }

    public function update($request)
    {
        DB::beginTransaction();
        try {
            $user_id = Auth::guard('api')->user()->id;
            $username = Auth::guard('api')->user()->username;

            $media = Media::where("id", $request['id'])->first();
            if (!$media) {
                throw new HttpException("Không tìm thấy tệp đính kèm");
            }
            $msg = $media->file_name;
            if (isset($request['file_name'])) {
                $msg = $media->file_name  . " thành " . $request["file_name"];
                $media->file_name = $request['file_name'];
            }
            $media->save();
            Activity::activityLog($msg, $media->object_id ?? null, "attachment", "rename_file", $user_id, ["username" => $username, "object_type" => $media->object_type ?? null]);

            DB::commit();
            $message = "Cập nhật tệp đính kèm thành công";

            return $media;
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            throw new HttpException($message);
        }
    }

    public function delete($request)
    {
    }

    public function deleteAttachment($request, $id)
    {
        DB::beginTransaction();
        try {
            $user_id = Auth::guard('api')->user()->id;
            $username = Auth::guard('api')->user()->username;

            $media = Media::findOrFail($id);

            $media_path = public_path($media->file_path);

            if (file_exists($media_path)) {
                File::delete($media_path);
            }

            $result = $media->delete();
            Activity::activityLog($media->file_name, $media->object_id, "attachment", "delete_file", $user_id, ["username" => $username, "object_type" => $media->object_type]);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            throw new HttpException($message);
        }
    }

    public function download($request, $id)
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
