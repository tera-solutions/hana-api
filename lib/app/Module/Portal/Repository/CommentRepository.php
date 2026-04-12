<?php

namespace App\Module\Portal\Repository;

use App\Helpers\Activity;
use App\Module\Portal\Model\Media;
use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Package\Exception\DatabaseException;
use Illuminate\Support\Facades\DB;
use Package\Exception\HttpException;
use App\Module\Portal\Model\Comment;
use App\Module\Portal\Model\Transaction;
use Illuminate\Support\Facades\Auth;

class CommentRepository extends BasicEntity implements RepositoryInterface
{

    public $table;

    public $primaryKey;

    public $fillable;

    public $hidden;

    public function __construct()
    {
        $activityLog = new Comment();
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
            $comment = Comment::with([
                "created_by",
                'media',
                'children',
                'children.created_by:id,username,avatar',
                'children.media'
            ])
                ->where("parent_id", null)
                ->select(["*", DB::raw("'comment' as object_type")]);

            if (isset($request->object_id) && $request->object_id) {
                $comment->where("object_id", $request->object_id);
            }

            if (isset($request->type) && $request->type) {
                $comment->where("type", $request->type);
            }

            $sort_field = "created_at";
            $sort_des = "desc";

            if (isset($request->order_field) && $request->order_field) {
                $sort_field = $request->order_field;
            }

            if (isset($request->order_by) && $request->order_by) {
                $sort_des = $request->order_by;
            }

            if (isset($request->limit) && $request->limit) {
                $limit = $request->limit;
            }

            $comment->orderBy($sort_field, $sort_des);

            $data = $comment->paginate($limit);

            return $data;
        } catch (\Exception $e) {
            $message = $e->getMessage();

            throw new HttpException($message);
        }
    }

    public function find($id)
    {
        $comment = Comment::with(["created_by"])->where("id", $id)->first();

        if (!$comment) {
            throw new HttpException("Không tìm thấy bình luận", 404);
        }
        $comment->object_type = "comment";

        return $comment;
    }

    public function create($request)
    {
        DB::beginTransaction();
        try {
            $comment = $this->CreateOrUpdate($request);
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

            if (isset($request->object_type) && $request->object_type === "order") {
                Transaction::where("id", $request->object_id)->update([
                    "view_status" => "new",
                    "updated_at" => now()
                ]);
            }
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

    public function update($data)
    {
        try {
            $this->id = $data[$this->primaryKey];
            $model = $this->CreateOrUpdate($data);
            if ($model) {
                return $model;
            }
            return false;
        } catch (\Exception $e) {
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
}
