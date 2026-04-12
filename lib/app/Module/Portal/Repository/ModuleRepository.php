<?php

namespace App\Module\Portal\Repository;

use App\Module\Portal\Model\Business;
use Illuminate\Support\Facades\DB;
use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Exception;
use App\Module\Portal\Model\Module;
use App\Module\Portal\Model\ModulePermission;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Package\Exception\DatabaseException;
use Package\Exception\HttpException;

class ModuleRepository extends BasicEntity implements RepositoryInterface
{

    public $table;

    public $primaryKey;

    public $fillable;

    public $hidden;

    public $roleRepository;
    public function __construct()
    {
        $module = new Module();
        $this->table = $module->getTable();
        $this->fillable = $module->getFillable();
        $this->hidden = $module->getHidden();
        $this->primaryKey = $module->getKeyName();
        $this->roleRepository = new RoleRepository();
        array_push($this->fillable, $this->primaryKey);
    }

    public function all($request)
    {
        $user = Auth::guard('api')->user();
        $module = Module::with([
            "created_by:id,code,username,full_name",
        ])->select(["*"]);
        $objectRequest = new Request();
        $objectRequest->merge(['role_id' => $user->role_id]);
        $data = $this->roleRepository->roleHasPermissionDetail($objectRequest);
        $moduleIds = [];
        foreach (collect($data)->toArray() as $item) {
            if (isset($item['group_control'])) {
                if (isset($item['group_control']['module'])) {
                    $moduleIds[] = $item['group_control']['module']['id'];
                }
            }
        }
        $module->whereIn('id', array_unique($moduleIds));

        if (isset($request->type) && $request->type) {
            $module->whereJsonContains('type', $request->type);
        }

        if (isset($request->keyword) && $request->keyword) {
            $module->where("title", "LIKE", "%$request->keyword%");
            $module->orWhere("sub_title", "LIKE", "%$request->keyword%");
        }

        if (isset($request->title) && $request->title) {
            $module->where("title", "LIKE", "%$request->title%");
            $module->orWhere("sub_title", "LIKE", "%$request->title%");
        }


        if (isset($request->code) && $request->code) {
            $module->where("code", "LIKE", "%$request->code%");
        }

        $sort_field = "created_at";
        $sort_des = "desc";

        if (isset($request->order_field) && $request->order_field) {
            $sort_field = $request->order_field;
        }

        if (isset($request->order_by) && $request->order_by) {
            $sort_des = $request->order_by;
        }

        $module->orderBy($sort_field, $sort_des);

        $data = $module->paginate($request->input('limit', 10));

        return  $data;
    }

    public function exceptionTimeout($user, $request)
    {
        if ($user->type == 'individual') {
            if ($request->type == 'individual') {
                if (now()->format('Y-m-d') > $user->expiration_time) {
                    throw new HttpException("Tài khoản hết hạn !", 210);
                }
            }
        } elseif ($user->type == 'owner') {
            $business = Business::where('id', $user->business_id)->first();
            if (!$business) {
                throw new HttpException("Không tìm thấy doanh nghiệp !");
            }
            if ($request->type == 'individual') {
                if (now()->format('Y-m-d') > $user->expiration_time) {
                    throw new HttpException("Tài khoản hết hạn !", 210);
                }
            }
            //
        } elseif ($user->type == 'member') {
            $business = Business::where('id', $user->business_id)->first();
            if (!$business) {
                throw new HttpException("Không tìm thấy doanh nghiệp !");
            }
            if ($request->type == 'individual') {
                if (now()->format('Y-m-d') > $user->expiration_time) {
                    throw new HttpException("Tài khoản hết hạn !", 210);
                }
            }
        }
    }

    public function paginateCustom($data)
    {
        $page = request()->input('page', 1);
        $perPage = request()->input('limit', 10);
        $dataPaginated = new LengthAwarePaginator(
            $data->forPage($page, $perPage)->values(),
            $data->count(),
            $perPage,
            $page
        );

        $dataPaginated->setPath(request()->url());

        return $dataPaginated;
    }

    public function find($id)
    {
        $data = Module::with([
            "created_by:id,code,username,full_name",
        ])->where("id", $id)->first();
        return $data;
    }

    public function create($data)
    {
        try {
            $result = $this->CreateOrUpdate($data);

            return $result;
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    public function createManyOfRow($data)
    {
        try {
            $result = $this->CreateManyRow($data);

            return $result;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function update($data)
    {
        try {
            $this->id = $data[$this->primaryKey];
            $model = $this->CreateOrUpdate($data);
            if ($model) {
                return true;
            }
            return false;
        } catch (Exception $e) {
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
            if ($model) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function delete($id)
    {

        $this->id = $id;
        $arrID = [$id];
        $model = $this->DeleteManyRow($arrID);

        if (!$model) {
            return false;
        }
        return true;
    }

    public function withErrors()
    {
        if ($this->error != null) {
            $path = storage_path('logs/package.log');
            $totalLine = count(file($path));
            $cotent = "\r\n #" . $totalLine . ' \error: ' . $this->error . ' at time /t:' . now();
            $this->WriteLog($path, $cotent);
        }

        $error = $this->error;

        if (env('APP_DEBUG') === false) {
            $error = "Internal server!";
        }

        return $error;
    }

    public function excuteSqlFile()
    {
        $file = app_path('Module/Portal/Repository/sql/module.sql');
        if (file_exists($file)) {
            $sql = file_get_contents($file);
            return \Illuminate\Support\Facades\DB::select($sql);
        }
    }
}
