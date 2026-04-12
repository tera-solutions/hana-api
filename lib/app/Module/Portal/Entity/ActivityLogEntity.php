<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Permission\ActivityLogPermission;
use App\Module\Portal\Repository\ActivityLogRepository;
use Package\Entity\AbstractEntity;
use App\Module\Portal\Validation\User\UserUpdateValidator;
use Package\Entity\EntityInterface;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class ActivityLogEntity extends AbstractEntity implements EntityInterface
{

    /**
     * @var ActivityLogRepository
     */
    protected $repository;

    /**
     * @var
     */
    protected $errors;

    /**
     *  @var array
     *
     */
    protected $permission;
    /**
     * RegisterEntity constructor.
     * @param MailRepository $repository
     * @param UserUpdateValidator $updateValidator
     */
    public function __construct(
        ActivityLogRepository $repository,
        ActivityLogPermission $permission
    ) {
        $this->repository = $repository;
        $this->permission = $permission;
    }
}
