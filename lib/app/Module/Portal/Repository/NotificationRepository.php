<?php

namespace App\Module\Portal\Repository;

use App\Jobs\SendMailJob;
use App\Module\Portal\Model\ActivityLog;
use App\Module\Portal\Model\Media;
use App\Module\Portal\Model\Notification;
use App\Module\Portal\Model\NotificationUser;
use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Package\Exception\DatabaseException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Package\Exception\HttpException;

class NotificationRepository extends BasicEntity implements RepositoryInterface
{

    public $table;

    public $primaryKey;

    public $fillable;

    public $hidden;

    public function __construct()
    {
        $activityLog = new Notification();
        $this->table =  $activityLog->getTable();
        $this->fillable =  $activityLog->getFillable();
        $this->hidden =  $activityLog->getHidden();
        $this->primaryKey =  $activityLog->getKeyName();
        array_push($this->fillable, $this->primaryKey);
    }

    public function all($request)
    {
        try {
            $user_id = Auth::guard('api')->user()->id;
            $limit = 10;
            $activity = Notification::with(["created_by"])
                ->where("parent_id", null)
                ->select([
                    "sys_notifications.*",
                ]);

            if (isset($request->object_id) && $request->object_id) {
                $activity->where("sys_notifications.object_id", $request->object_id);
            }

            if (isset($request->object_type) && $request->object_type) {
                $activity->where("sys_notifications.object_type", $request->object_type);
            }

            if (isset($request->class_id) && $request->class_id) {
                $activity->where("sys_notifications.class_id", $request->class_id);
            }

            if (isset($request->type) && $request->type) {
                $activity->where("sys_notifications.type", $request->type);
            }

            if (isset($request->title) && $request->title) {
                $activity->where("sys_notifications.title", "LIKE", "%$request->title%");
            }

            if (isset($request->content) && $request->content) {
                $activity->where("sys_notifications.content", "LIKE", "%$request->content%");
            }

            if (isset($request->start_date) && $request->start_date) {
                $start_date = Carbon::createFromFormat('d/m/Y', $request->start_date);
                $activity->whereDate('sys_notifications.created_at', '>=', $start_date->toDateString());
            }

            if (isset($request->end_date) && $request->end_date) {
                $end_date = Carbon::createFromFormat('d/m/Y', $request->end_date);
                $activity->whereDate('sys_notifications.created_at', '<=', $end_date->toDateString());
            }

            if (isset($request->per_page) && $request->per_page) {
                $limit = $request->per_page;
            } elseif (isset($request->limit) && $request->limit) {
                $limit = $request->limit;
            }

            $activity->orderBy('created_at', "desc");

            $data = $activity->paginate($limit);
            $data->getCollection()->transform(function ($item, $key) use ($data, $user_id) {
                $checkView = NotificationUser::where('user_id', $user_id)->where('notification_id', $item->id)->where('is_view', 1)->first();
                $item->is_view = $checkView ? true : false;
                return $item;
            });
            return $data;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new HttpException($message);
        }
    }

    public function find($id)
    {
        $notification = Notification::with(["created_by", "users", "children", "children.created_by", "media"])->where("id", $id)->first();

        if (!$notification) {
            throw new HttpException("Không tìm thấy được dữ liệu");
        }
        $notification->object_type = "comment";

        return $notification;
    }

    public function create($request)
    {
        DB::beginTransaction();

        try {
            $input = $request->only(['title', 'content', 'object_id', 'object_type', 'class_id', 'parent_id', 'type']);
            $user_id = Auth::guard('api')->user()->id;

            $input['created_by'] = $user_id;

            $notification = Notification::create($input);

            if (!$notification) {
                DB::rollBack();
                throw new DatabaseException("Lỗi khi thêm dữ liệu");
            }

            if (!empty($request->users)) {
                $insertData = [];
                foreach ($request->users as $value) {
                    $item = [
                        "user_id" => $value,
                        "notification_id" => $notification->id,
                        "is_view" => 0,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ];

                    array_push($insertData, $item);
                }

                if (count($insertData) > 0) {
                    $result = NotificationUser::insert($insertData);

                    if (!$result) {
                        DB::rollBack();
                        throw new DatabaseException("Lỗi khi thêm dữ liệu");
                    }
                }
            }
            DB::commit();
            return $notification;
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            throw new HttpException($message);
        }
    }

    public function createManyOfRow($data)
    {
    }

    public function update($data)
    {
        try {
            $this->id = $data[$this->primaryKey];
            $model = $this->CreateOrUpdate($data);
            if (!$model) {
                throw new DatabaseException("Lỗi khi cập nhật dữ liệu");
            }
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
            return $model;
        } catch (DatabaseException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function read($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $user_id = Auth::guard('api', $id)->user()->id;
                $notify = Notification::where("id", $id)->first();
                if (!$notify) {
                    throw new HttpException("Không tìm thấy dữ liệu", 500);
                }
                NotificationUser::updateOrCreate([
                    "user_id" => $user_id,
                    "notification_id" => $id,
                ], [
                    "is_view" => 1
                ]);
                return $notify;
            });
        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new HttpException($message);
        }
    }
}
