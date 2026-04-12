<?php

namespace App\Module\Portal\Repository;


use App\Module\Portal\Model\ModulePermission;
use Illuminate\Support\Facades\Http;
use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Package\Exception\DatabaseException;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Package\Exception\HttpException;

class MemberRepository extends BasicEntity implements RepositoryInterface
{

  public $table;

  public $primaryKey;

  public $fillable;

  public $hidden;

  public function __construct()
  {
    $user = new User();
    $this->table =  $user->getTable();
    $this->fillable =  $user->getFillable();
    $this->hidden =  $user->getHidden();
    $this->primaryKey =  $user->getKeyName();
    array_push($this->fillable, $this->primaryKey);
  }

  public function all($request)
  {
    $business_id = Auth::guard('api')->user()->business_id;
    if (!$business_id) {
      return null;
    }
    $member = User::with(['modules.module', 'modules', 'role'])->where('business_id', $business_id)->select([
      "*",
    ]);

    if (isset($request->role_id)  && $request->role_id) {
      $member->where('role_id', $request->role_id);
    } else {
      $member->where('type', 'member');
    }

    if (isset($request->keyword)  && $request->keyword) {
      $member->where(function ($q) use ($request) {
        $keyword = strtolower($request->keyword);
        $q->where('full_name', 'LIKE', "%$keyword%");
      });
    }

    if (isset($request->department)  && $request->department) {
      $member->where('department', $request->department);
    }

    if (isset($request->job_title)  && $request->job_title) {
      $member->where('job_title', $request->job_title);
    }


    if (isset($request->no_module)) {
      if ($request->no_module == 1) {
        $arrFilter = [];
        $idsMembers = User::where('type', 'member')->where('business_id', $business_id)->pluck('id')->toArray();
        foreach ($idsMembers as $memberValue) {
          $flag = true;
          $getModuleMember = ModulePermission::where('user_id', $memberValue)->where('type', "business")->get()->toArray();
          foreach ($getModuleMember as $module) {
            if ($module['module_id'] == $request->module_id) {
              $flag = false;
            }
          }
          if ($flag) {
            $arrFilter[] = $memberValue;
          }
        }
        $member->whereIn('id', $arrFilter);
      } elseif ($request->no_module == 0) {
        if (isset($request->module_id)  && $request->module_id) {
          $member->whereHas('modules', function ($query) use ($request) {
            $query->where('module_id', $request->module_id);
            $query->where('type', 'business');
          });
        }
        $member->has('modules');
      }
    }

    $sort_field = "created_at";
    $sort_des = "desc";

    if (isset($request->order_field) && $request->order_field) {
      $sort_field = $request->order_field;
    }

    if (isset($request->order_by) && $request->order_by) {
      $sort_des = $request->order_by;
    }

    $member->orderBy($sort_field, $sort_des);

    $data = $member->paginate($request->limit);
    return $data;
  }

  public function find($id)
  {
    $data = User::with(['modules.module', 'user_created', 'user_updated', 'role'])->where('id', $id)->first();
    if (!$data) {
      throw new HttpException("Không tìm thấy dữ liệu !");
    }
    return $data;
  }

  public function create($data)
  {
    $token = $this->getCurrentBearerToken();
    $business_id = request()->header('business-id');
    $url = env('CREATE_MEMBER_ADMIN') . '/api/administrator/user/create';
    $response =  Http::withHeaders([
      'Accept' => '*/*',
      'Connection' => 'keep-alive',
      'Content-Type' => 'application/json',
      'Sec-Fetch-Mode' => 'cors',
      'Sec-Fetch-Site' => 'same-site',
      'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
      'authorization' => 'Bearer ' . $token,
      'business-id' => $business_id,
      'device-code' => 'yfUW52kpfUKH',
    ])
      ->post($url, [
        "username" => $data['username'],
        "password" => $data['password'] ?? null,
        "is_active" => "1",
        "business_id" => $business_id,
        "full_name" => $data['full_name'],
        "email" => $data['email'],
        "phone" => $data['phone'],
        "job_title" => $data['job_title'] ?? null,
        "department" => $data['department'] ?? null,
        "role_id" => $data['role_id'] ?? null,
        "file_upload" => $data['file_upload'] ?? null,
        "type" => "member"
      ]);
    return $response;
  }

  public function callAPICreateEmployeeAfterCreateUser($apiUrl, $bearerToken, $userId)
  {
    try {
      $client = new Client();
      $params = ['user_id' => $userId];
      $response = $client->get($apiUrl, [
        'headers' => [
          'Authorization' => 'Bearer ' . $bearerToken,
          'Accept' => 'application/json',
        ],
        'query' => $params,
      ]);
      $result = $response->getBody()->getContents();
      return $result;
    } catch (\Exception $e) {
      return $e->getMessage();
    }
  }

  public function getCurrentBearerToken()
  {
    $authorizationHeader = request()->header('Authorization');
    if ($authorizationHeader && strpos($authorizationHeader, 'Bearer ') === 0) {
      $bearerToken = substr($authorizationHeader, 7);
      return $bearerToken;
    }
    return null;
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

  /**
   * @throws HttpException
   * @throws DatabaseException
   */
  public function update($data)
  {
    try {
      $user = User::where('id', $data['id'])->first();

      if (!$user) {
        throw new HttpException("Không tìm thấy dữ liệu !", 400, []);
      }
      $checkEmailExist = User::where('email', trim(strtolower($data['email'])))->where('id', '!=', $data['id'])->first();

      $checkUserNameExist = User::where('username', trim(strtolower($data['username'])))->where('id', '!=', $data['id'])->first();
      if ($checkEmailExist) {
        throw new HttpException("Email đã tồn tại !", 400, []);
      }
      if ($checkUserNameExist) {
        throw new HttpException("Username đã tồn tại !", 400, []);
      }
      $data['status_account'] = 'is_active';
      $data['type'] = 'member';
      $data['business_id'] = Auth::guard('api')->user()->business_id;
      $data['is_active'] = 1;
      $data['updated_by'] = Auth::guard('api')->user()->id;
      $user->update($data);

      $this->updateEmployee($user);
      return $user;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  public function updateEmployee($data)
  {
    $token = $this->getCurrentBearerToken();
    $business_id = request()->header('business-id');
    $url = env('CRM_URL_EMPLOYEE') . '/api/hrm/employee/update-by-data';
    return Http::withHeaders([
      'Accept' => '*/*',
      'Connection' => 'keep-alive',
      'Content-Type' => 'application/json',
      'Sec-Fetch-Mode' => 'cors',
      'Sec-Fetch-Site' => 'same-site',
      'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
      'authorization' => 'Bearer ' . $token,
      'business-id' => $business_id,
      'device-code' => 'yfUW52kpfUKH',
    ])
      ->put($url, [
        "user_id" => $data['id'],
        "username" => $data['username'],
        "full_name" => $data['full_name'],
        "email" => $data['email'],
        "phone" => $data['phone'],
        "job_title" => $data['job_title'] ?? null,
        "department" => $data['department'] ?? null,
        "avatar" => $data['avatar'] ?? null,
      ]);
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
      $user = User::where('id', $id)->first();

      if (!$user) {
        throw new HttpException("Không tìm thấy dữ liệu !");
      }
      $user->delete();

      return   $user;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  public function updateStatus($request)
  {
    try {
      $user = User::where('id', $request->member_id)->first();
      $business_id = Auth::guard('api')->user()->business_id;
      if (!$user) {
        throw new HttpException("Không tìm thấy dữ liệu !");
      }

      if ($user->type != 'member') {
        throw new HttpException("Đây không phải là thành viên !");
      }

      if ($user->business_id !=  $business_id) {
        throw new HttpException("Đây không phải là thành viên của doanh nghiệp bạn !");
      }

      $user->is_active = $request->is_active;
      $user->save();

      if ($request->is_active == 0) {
        $userTokens = $user->tokens->pluck('id');
        $user->tokens()->whereIn('id', $userTokens)->delete();
      }

      return $user;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  public function configRole($request)
  {
    try {
      $user = User::where('id', $request->member_id)->first();
      $business_id = Auth::guard('api')->user()->business_id;
      if (!$user) {
        throw new HttpException("Không tìm thấy dữ liệu !");
      }

      if ($user->type != 'member') {
        throw new HttpException("Đây không phải là thành viên !");
      }

      if ($user->business_id !=  $business_id) {
        throw new HttpException("Đây không phải là thành viên của doanh nghiệp bạn !");
      }

      $user->role_id = $request->role_id;
      $user->save();
      //
      $userTokens = $user->tokens->pluck('id');
      $user->tokens()->whereIn('id', $userTokens)->delete();
      //
      return   $user;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }
}
