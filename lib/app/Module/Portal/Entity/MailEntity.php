<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Permission\MailPermission;
use App\Module\Portal\Repository\MailRepository;
use App\Module\Portal\Validation\Mail\SendMailValidator;
use Package\Entity\AbstractEntity;
use App\Module\Portal\Validation\User\UserUpdateValidator;
use Package\Entity\EntityInterface;
use Package\Exception\ValidationException;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)//
 */
class MailEntity extends AbstractEntity implements EntityInterface
{

    /**
     * @var MailRepository
     */
    protected $repository;

    /**
     * @var SendMailValidator
     */
    protected $sendMailValidator;


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
        MailRepository $repository,
        SendMailValidator $sendMailValidator,
        MailPermission $permission
    ) {
        $this->repository = $repository;
        $this->sendMailValidator = $sendMailValidator;
        $this->permission = $permission;
    }

    public function sendMail($request)
    {
        if (!$this->sendMailValidator->with($request->all())->passes()) {

            $errors = $this->sendMailValidator->errors();

            throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $errors);
        }
        $this->repository->sendMail($request);
    }
}
