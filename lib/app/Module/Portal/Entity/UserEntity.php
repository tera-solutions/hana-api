<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Permission\UserPermission;
use Package\Entity\AbstractEntity;
use Package\Entity\EntityInterface;
use App\Module\Portal\Repository\UserRepository;
use App\Module\Portal\Validation\User\UserCreateValidator;
use App\Module\Portal\Validation\User\UserUpdateValidator;
use Illuminate\Support\Facades\DB;
use Package\Exception\AuthorizationException;
use Package\Exception\HttpException;
use Package\Exception\ValidationException;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class UserEntity extends AbstractEntity implements EntityInterface
{

    /**
     * @var UserRepository
     */
    protected $repository;

    /**
     * @var UserCreateValidator
     */
    protected $createValidator;

    /**
     * @var UserUpdateValidator
     */
    protected $updateValidator;

    /**
     * @var
     */
    protected $errors;

    /**
     * @var UserPermission
     */
    protected $permission;

    /**
     * RegisterEntity constructor.
     * @param UserRepository $repository
     * @param UserCreateValidator $createValidator
     * @param UserUpdateValidator $updateValidator
     * @param UserPermission $permission
     */
    public function __construct(
        UserRepository $repository,
        UserCreateValidator $createValidator,
        UserUpdateValidator $updateValidator,
        UserPermission $permission
    ) {
        $this->repository = $repository;
        $this->createValidator = $createValidator;
        $this->updateValidator = $updateValidator;
        $this->permission = $permission;
    }

    public function getProfile()
    {
        return $this->repository->getProfile();
    }


    public function changePassword($input)
    {
        return $this->repository->changePassword($input);
    }

    public function updateProfile($data)
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
                return $this->repository->updateProfile($data);
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function updateAvatar($input)
    {
        return $this->repository->updateAvatar($input);
    }

    public function changeSetting($input)
    {
        return $this->repository->changeSetting($input);
    }

    public function changeLanguage($input)
    {
        return $this->repository->changeLanguage($input);
    }
}
