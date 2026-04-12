<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Permission\CommentPermission;
use App\Module\Portal\Repository\CommentRepository;
use App\Module\Portal\Repository\MailRepository;
use App\Module\Portal\Validation\Comment\CommentCreateValidator;
use App\Module\Portal\Validation\Comment\CommentUpdateValidator;
use Package\Entity\AbstractEntity;
use App\Module\Portal\Validation\User\UserUpdateValidator;
use Package\Entity\EntityInterface;
use Package\Exception\ValidationException;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class CommentEntity extends AbstractEntity implements EntityInterface
{

    /**
     * @var ActivityLogEntity
     */
    protected $repository;

    /**
     * @var CommentCreateValidator
     */
    protected $createValidator;

    /**
     * @var CommentUpdateValidator
     *
     * */
    protected $updateValidator;


    /**
     * @var
     */
    protected $errors;

    /**
     * @var CommentPermission
     */
    protected $permission;
    /**
     * RegisterEntity constructor.
     * @param MailRepository $repository
     * @param UserUpdateValidator $updateValidator
     */
    public function __construct(
        CommentRepository $repository,
        CommentCreateValidator $createValidator,
        CommentUpdateValidator $updateValidator,
        CommentPermission $permission
    ) {
        $this->repository = $repository;
        $this->createValidator = $createValidator;
        $this->updateValidator = $updateValidator;
        $this->permission = $permission;
    }
}
