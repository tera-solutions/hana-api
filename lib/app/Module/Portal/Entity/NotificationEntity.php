<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Permission\NotificationPermission;
use App\Module\Portal\Repository\NotificationRepository;
use App\Module\Portal\Validation\Notification\NotificationCreateValidator;
use App\Module\Portal\Validation\Notification\NotificationUpdateValidator;
use Package\Entity\AbstractEntity;
use Package\Entity\EntityInterface;
use Package\Exception\ValidationException;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class NotificationEntity extends AbstractEntity implements EntityInterface
{

    /**
     * @var NotificationRepository
     */
    protected $repository;

    /**
     * @var NotificationCreateValidator
     */
    protected $createValidator;

    /**
     * @var NotificationUpdateValidator
     *
     */
    protected $updateValidator;

    /**
     * @var
     */
    protected $errors;

    /**
     * @var NotificationPermission
     */
    protected $permission;
    /**
     * RegisterEntity constructor.
     * @param NotificationRepository $repository
     * @param NotificationCreateValidator $createValidator
     * @param NotificationUpdateValidator $updateValidator
     * @param NotificationPermission $permission
     */
    public function __construct(
        NotificationRepository $repository,
        NotificationCreateValidator $createValidator,
        NotificationUpdateValidator $updateValidator,
        NotificationPermission $permission
    ) {
        $this->repository = $repository;
        $this->createValidator = $createValidator;
        $this->updateValidator = $updateValidator;
        $this->permission = $permission;
    }

    public function read($id)
    {
        return $this->repository->read($id);
    }

    public function create($request)
    {
        if (!$this->createValidator->with($request->all())->passes()) {
            $errors = $this->createValidator->errors();
            throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $errors);
        }
        $this->repository->create($request);
    }
}
