<?php

namespace App\Module\Portal\Repository;

use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Exception;
use Package\Exception\DatabaseException;
use App\Models\Media;
use App\Module\Portal\Model\Business;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Package\Exception\HttpException;

class UserRepository extends BasicEntity implements RepositoryInterface
{

    public $table;

    public $primaryKey;

    public $fillable;

    public $hidden;

    public $memberRepo;

    public function __construct(MemberRepository $memberRepo)
    {
        $user = new User();
        $this->table =  $user->getTable();
        $this->fillable =  $user->getFillable();
        $this->hidden =  $user->getHidden();
        $this->primaryKey =  $user->getKeyName();
        array_push($this->fillable, $this->primaryKey);
        $this->memberRepo = $memberRepo;
    }

    public function all($request)
    {
        $user = User::select([
            "id",
        ]);

        $sort_field = "created_at";
        $sort_des = "desc";

        if (isset($request->order_field) && $request->order_field) {
            $sort_field = $request->order_field;
        }

        if (isset($request->order_by) && $request->order_by) {
            $sort_des = $request->order_by;
        }

        $user->orderBy($sort_field, $sort_des);

        $data =  $user->paginate($request->limit);
        return $data;
    }

    public function find($id)
    {
        $data = User::select([
            "id",
        ])->find($id);
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

    public function getProfile()
    {
        $user_id = Auth::guard('api')->user()->id;
        $user = User::where('id', $user_id)
            ->where("status", "1")
            ->select([
                "*"
            ])
            ->first();
        if ($user) {
            return $user;
        }
        return false;
    }

    public function changePassword($request)
    {
        $userLogin = Auth::guard('api')->user();
        if (!$userLogin) {
            throw new HttpException("Không tìm thấy người dùng !");
        }
        $user_id = $userLogin->id;
        $user = User::where('id',  $user_id)->first();
        if (!$user) {
            throw new HttpException("Không tìm thấy người dùng !");
        }
        if (!Hash::check($request->old_password, $user->password)) {
            throw new HttpException("Mật khẩu cũ không đúng !");
        }
        $user->password = Hash::make($request->new_password);
        $user->save();

        //
        Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getBearerToken(),
        ])->post('https://auth-api.teravn.com/api/auth/logout');
        return $user;
    }

    function getBearerToken()
    {
        $authorizationHeader = request()->header('Authorization');

        if ($authorizationHeader !== false && strpos($authorizationHeader, 'Bearer ') === 0) {
            return substr($authorizationHeader, 7);
        }

        return null;
    }

    public function updateProfile($data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $userId = Auth::guard('api')->user()->id;
                $user = User::where('id', $userId)->first();
                if (!$user) {
                    throw new HttpException("Không tìm thấy người dùng !");
                }

                if ($user->type == 'owner') {
                    $business_id = $user->business_id;
                    if ($business_id) {
                        $getBusiness = Business::where('id', $business_id)->first();
                        if ($getBusiness) {
                            $getBusiness->update([
                                'owner_name' => $data['full_name'] ?? null,
                                'owner_phone' => $data['phone'] ?? null,
                                'owner_department' => $data['department'] ?? null,
                                'owner_job_title' => $data['job_title'] ?? null,
                            ]);
                        }
                    }
                }
                $user->update($data);
                // $this->memberRepo->updateEmployee($user);
                return $user;
            });
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    public function updateAvatar($request)
    {
        $user = Auth::guard('api')->user();
        if ($user) {
            $user->avatar = $request['avatar'];
            $user->save();
            return true;
        }
        return false;
    }

    public function changeSetting($request)
    {
        return true;
    }

    public function changeLanguage($request)
    {
        return true;
    }
}
