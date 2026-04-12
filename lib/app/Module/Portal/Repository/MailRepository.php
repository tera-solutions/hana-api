<?php

namespace App\Module\Portal\Repository;

use App\Jobs\SendMailJob;
use App\Module\Portal\Model\Media;
use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Package\Exception\DatabaseException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Package\Exception\HttpException;

class MailRepository extends BasicEntity implements RepositoryInterface
{

    public $table;

    public $primaryKey;

    public $fillable;

    public $hidden;

    public function __construct()
    {
        $user = new Media();
        $this->table =  $user->getTable();
        $this->fillable =  $user->getFillable();
        $this->hidden =  $user->getHidden();
        $this->primaryKey =  $user->getKeyName();
        array_push($this->fillable, $this->primaryKey);
    }

    public function all($request)
    {
        try {
            $limit = 10;
            if (empty($request->object_id)) {
                throw new HttpException("Không tìm thấy lịch sử",  500);
            }

            $activity = Media::with(["created_by"])
                ->leftJoin("users", "users.id", "media.uploaded_by")
                ->select([
                    "media.*"
                ]);

            if (isset($request->username) && $request->username) {
                $activity->where("users.username", 'LIke', "%$request->username%");
            }

            if (isset($request->object_id) && $request->object_id) {
                $activity->where("media.object_id", $request->object_id);
            }

            if (isset($request->object_type) && $request->object_type) {
                $activity->where("media.object_type", $request->object_type);
            }

            if (isset($request->file_type) && $request->file_type) {
                $activity->where("media.file_type", $request->file_type);
            }

            if (isset($request->file_name) && $request->file_name) {
                $activity->where("media.file_name", "LIKE", "%$request->file_name%");
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

            if (isset($request->limit) && $request->limit) {
                $limit = $request->limit;
            }


            $activity->orderBy('media.created_at', "desc");

            $data = $activity->paginate($limit);

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
                throw new HttpException("Không tìm thấy lịch sử",  500);
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

    public function create($data)
    {
    }
    public function createManyOfRow($data)
    {
        try {
            $model = $this->CreateManyRow($data);

            return $model;
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    public function update($data)
    {
        try {
            $this->id = $data[$this->primaryKey];
            $model = $this->CreateOrUpdate($data);

            return $model;
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    public function allTrash($pagin = null)
    {
        return  $this->findAllTrash($pagin);
    }

    public function trash($id, $trash = true)
    {
        try {
            $this->id = $id;
            $model = $this->TrashOrRecover($id, $trash);

            return $model;
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $this->id = $id;
            $arrID = [$id];
            $model = $this->DeleteManyRow($arrID);

            $listFile = Media::where("object_id", $id)->get();

            if (count($listFile) > 0) {
                foreach ($listFile as $key => $value) {
                    $media = Media::findOrFail($value->id);

                    $media_path = public_path($media->file_path);

                    if (file_exists($media_path)) {
                        File::delete($media_path);
                    }

                    $media->delete();
                }
            }

            return $model;
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    public function sendMail($request)
    {
        DB::beginTransaction();

        try {
            $mail = $request->email;

            $data = [
                'mail' => $mail,
                'description' => isset($request->description) ? $request->description : "",
            ];

            $files = isset($request->attachments) ? $request->attachments : [];
            $job = (new SendMailJob($mail, $data, $files));
            dispatch($job);

            $message = "Đã gửi mật khẩu về email " . $request->email;

            return $message;
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            throw new HttpException($message);
        }
    }
}
