<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Permission\ModulePermission;
use Package\Entity\AbstractEntity;
use Package\Entity\EntityInterface;
use App\Module\Portal\Repository\ModuleRepository;
use App\Module\Portal\Validation\Module\ModuleCreateValidator;
use App\Module\Portal\Validation\Module\ModuleUpdateValidator;


/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class ModuleEntity extends AbstractEntity implements EntityInterface
{

    /**
     * @var ModuleRepository
     */
    protected $repository;

    /**
     * @var ModuleCreateValidator
     */
    protected $createValidator;

    /**
     * @var ModuleUpdateValidator
     */
    protected $updateValidator;

    /**
     * @var
     */
    protected $errors;


    protected $permission;
    /**
     * RegisterEntity constructor.
     * @param ModuleRepository $repository
     * @param ModuleCreateValidator $createValidator
     * @param ModuleUpdateValidator $updateValidator
     */
    public function __construct(
        ModuleRepository $repository,
        ModuleCreateValidator $createValidator,
        ModuleUpdateValidator $updateValidator,
        ModulePermission $permission
    ) {
        $this->repository = $repository;
        $this->createValidator = $createValidator;
        $this->updateValidator = $updateValidator;
        $this->permission = $permission;
    }
}
