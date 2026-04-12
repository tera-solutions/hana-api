<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Model\Module;
use App\Module\Portal\Model\ModulePermission;
use Package\Entity\AbstractEntity;
use Package\Entity\EntityInterface;
use Package\Exception\ValidationException;
use App\Module\Portal\Repository\MemberRepository;
use App\Module\Portal\Permission\MemberPermission;
use App\Module\Portal\Validation\Member\MemberCreateValidator;
use App\Module\Portal\Validation\Member\MemberUpdateValidator;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Exception\AuthorizationException;
use Package\Exception\HttpException;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class MemberEntity extends AbstractEntity implements EntityInterface
{

  /**
   * @var MemberRepository
   */
  protected $repository;

  /**
   * @var MemberCreateValidator
   */
  protected $createValidator;

  /**
   * @var MemberUpdateValidator
   *
   * */
  protected $updateValidator;


  /**
   * @var
   */
  protected $errors;

  /**
   * @var MemberPermission
   */
  protected $permission;
  /**
   * RegisterEntity constructor.
   * @param MemberRepository $repository
   * @param MemberCreateValidator $updateValidator
   */
  public function __construct(
    MemberRepository $repository,
    MemberCreateValidator $createValidator,
    MemberUpdateValidator $updateValidator,
    MemberPermission $permission
  ) {
    $this->repository = $repository;
    $this->createValidator = $createValidator;
    $this->updateValidator = $updateValidator;
    $this->permission = $permission;
  }

  public function create($data)
  {
    try {
      return DB::transaction(function () use ($data) {
        if (!$this->permission) {
          throw new AuthorizationException();
        }

        if (!$this->permission->checkCreate()) {
          $msg = $this->permission->getMessage();
          throw new AuthorizationException($msg);
        }

        if (!$this->createValidator->with($data)->passes()) {
          $this->errors = $this->createValidator->errors();
          throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $this->errors);
        }

        return $this->repository->create($data);
      });
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function update($data)
  {
    try {
      return DB::transaction(function () use ($data) {
        if (!$this->permission) {
          throw new AuthorizationException();
        }

        if (!$this->permission->checkUpdate()) {
          $msg = $this->permission->getMessage();
          throw new AuthorizationException($msg);
        }

        if (!$this->updateValidator->with($data)->passes()) {
          $this->errors = $this->updateValidator->errors();
          throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $this->errors);
        }
        $avatar = null;
        if (isset($data['file_upload']) && $data['file_upload']) {
          if (isset($data['file_upload']['url'])) {
            $urlFile = $data['file_upload']['url'];
            $avatar = $urlFile;
          }
        }
        $data['avatar'] = $avatar;
        $result = $this->repository->update($data);
        return $result;
      });
    } catch (HttpException $e) {
      throw new HttpException($e->getMessage());
    }
  }

  public function changePassword($request)
  {
    try {
      return DB::transaction(function () use ($request) {
        $user = User::where('id', $request->member_id)->first();
        if (!$user) {
          throw new HttpException("Không tìm thấy người dùng !");
        }

        $password = bcrypt($request->password);

        $user->password = $password;
        $user->save();
        $userTokens = $user->tokens->pluck('id');
        $user->tokens()->whereIn('id', $userTokens)->delete();
        return true;
      });
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function permission($request)
  {
    try {
      return DB::transaction(function () use ($request) {
        $user = User::where('id', $request->member_id)->first();
        if (!$user) {
          throw new HttpException("Không tìm thấy người dùng !");
        }

        if ($user->type != 'member') {
          throw new HttpException("Người dùng này không phải là thành viên !");
        }

        if (empty($request->applications)) {
          throw new HttpException("Vui lòng chọn ứng dụng !");
        }
        $modules = Module::whereIn("id", $request->applications)->get();
        ModulePermission::where('user_id', $request->member_id)->delete();

        foreach ($modules as $key => $module) {
          foreach ($module->type as $type) {
            ModulePermission::create([
              "type" => $type,
              'user_id' => $request->member_id,
              'module_id' => $module->id,
              'created_at' => now()
            ]);
          }
        }

        return true;
      });
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function addToModule($request)
  {
    try {
      return DB::transaction(function () use ($request) {
        $module = Module::where('id', $request->module_id)->first();
        if (!$module) {
          throw new HttpException("Không tìm thấy ứng dụng !");
        }

        if (!empty($request->members)) {
          foreach ($request->members as $key => $member) {
            $check = ModulePermission::where('module_id', $request->module_id)->where('user_id', $member)->where('type', 'business')->first();
            if (!$check) {
              ModulePermission::create(['module_id' => $request->module_id, 'user_id' => $member, 'type' => 'business', 'created_at' => now()]);
            }
          }
        }

        return true;
      });
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function removeMemberModule($request)
  {
    try {
      return DB::transaction(function () use ($request) {
        $module = Module::where('id', $request->module_id)->first();
        if (!$module) {
          throw new HttpException("Không tìm thấy ứng dụng !");
        }

        if (!empty($request->members)) {
          foreach ($request->members as $key => $member) {
            ModulePermission::where('module_id', $request->module_id)->where('user_id', $member)->where('type', 'business')->delete();
          }
        }

        return true;
      });
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function updateStatus($request)
  {
    if (!$this->permission) {
      throw new AuthorizationException();
    }

    if (!$this->permission->checkKey('a')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->updateStatus($request);
  }

  public function configRole($request)
  {
    if (!$this->permission) {
      throw new AuthorizationException();
    }

    if (!$this->permission->checkKey('a')) {
      $msg = $this->permission->getMessage();
      throw new AuthorizationException($msg);
    }
    return $this->repository->configRole($request);
  }
}
