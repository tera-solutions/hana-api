<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Permission\AttachmentPermission;
use App\Module\Portal\Repository\AttachmentRepository;
use App\Module\Portal\Validation\Attachment\AttachmentUpdateValidator;
use Package\Entity\AbstractEntity;
use App\Module\Portal\Validation\User\UserUpdateValidator;
use Package\Entity\EntityInterface;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class AttachmentEntity extends AbstractEntity implements EntityInterface
{

    /**
     * @var ActivityLogEntity
     */
    protected $repository;

    /**
     * @var AttachmentUpdateValidator
     *
     * */
    protected $updateValidator;


    /**
     * @var
     */
    protected $errors;

    /**
     * @var AttachmentPermission
     */
    protected $permission;
    /**
     * RegisterEntity constructor.
     * @param AttachmentRepository $repository
     * @param UserUpdateValidator $updateValidator
     */
    public function __construct(
        AttachmentRepository $repository,
        AttachmentUpdateValidator $updateValidator,
        AttachmentPermission $permission
    ) {
        $this->repository = $repository;
        $this->updateValidator = $updateValidator;
        $this->permission = $permission;
    }

    public function deleteAttachment($request, $id)
    {
        return $this->repository->deleteAttachment($request, $id);
    }

    public function download($request, $id)
    {
        return $this->repository->download($request, $id);
    }
}
