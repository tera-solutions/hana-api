<?php

namespace App\Module\Portal\Repository;

use App\Jobs\SendMailJob;
use App\Module\Portal\Model\ActivityLog;
use App\Module\Portal\Model\Media;
use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Package\Exception\DatabaseException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Package\Exception\HttpException;

class ActivityLogRepository extends BasicEntity implements RepositoryInterface
{

    public $table;

    public $primaryKey;

    public $fillable;

    public $hidden;

    public function __construct()
    {
        $activityLog = new ActivityLog();
        $this->table =  $activityLog->getTable();
        $this->fillable =  $activityLog->getFillable();
        $this->hidden =  $activityLog->getHidden();
        $this->primaryKey =  $activityLog->getKeyName();
        array_push($this->fillable, $this->primaryKey);
    }

    public function all($request)
    {
        try {
            $limit = 10;

            // if (empty($request->object_id)) {
            //     throw new HttpException("Không tìm thấy lịch sử",  500);
            // }

            $activity = ActivityLog::with(["created_by"])
                ->leftJoin("users", "users.id", "sys_activity_logs.user_id")
                ->leftJoin("page_types as type1", "type1.code", "sys_activity_logs.type")
                ->leftJoin("page_types as type2", "type2.code", "sys_activity_logs.action_type")
                ->select([
                    "sys_activity_logs.*",
                    DB::raw("type1.text as type_text"),
                    DB::raw("type2.text as action_type_text")
                ]);

            if (isset($request->username) && $request->username) {
                $activity->where("users.username", 'LIke', "%$request->username%");
            }

            if (isset($request->object_id) && $request->object_id) {
                $activity->where("sys_activity_logs.object_id", $request->object_id);
            }

            if (isset($request->object_type) && $request->object_type) {
                $activity->where(function ($query) use ($request) {
                    $query->where("sys_activity_logs.type", $request->object_type);
                });
            }

            if (isset($request->action_type) && $request->action_type) {
                $activity->where("sys_activity_logs.action_type", $request->action_type);
            }

            if (isset($request->content) && $request->content) {
                $activity->where("sys_activity_logs.content", "LIKE", "%$request->content%");
            }

            $start_date = Carbon::now();

            if (!empty($request->start_date)) {
                $start_date = Carbon::createFromFormat('d/m/Y', $request->start_date);
                $activity->whereDate('sys_activity_logs.created_at', '>=', $start_date);
            }

            if (!empty($request->end_date)) {
                $end_date = Carbon::createFromFormat('d/m/Y', $request->end_date);
                $activity->whereDate('sys_activity_logs.created_at', '<=', $end_date);
            }

            if (isset($request->limit) && $request->limit) {
                $limit = $request->limit;
            }

            $activity->orderBy('created_at', "desc");

            $data = $activity->paginate($limit);

            return $data;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new HttpException($message);
        }
    }

    public function find($id)
    {
    }

    public function create($data)
    {
    }
    public function createManyOfRow($data)
    {
    }

    public function update($data)
    {
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
}
