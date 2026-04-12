<?php


namespace Package\Entity;

use Illuminate\Support\Facades\Auth;
use Package\Exception\AuthorizationException;
use Package\Exception\ValidationException;

/**
 *
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/12/2020
 * Time: 2:45 PM
 *
 * @property  errors
 */
abstract class AbstractEntity
{

    protected $repository;

    protected $createValidator;

    protected $updateValidator;

    protected $permission;

    protected $errors;

    /**
     * All
     *
     * @param array $filter
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function all($filter = [])
    {
        if (!$this->permission) {
            throw new AuthorizationException();
        }

        if (!$this->permission->checkList()) {
            $msg = $this->permission->getMessage();
            throw new AuthorizationException($msg);
        }
        return $this->repository->all($filter);
    }

    /**
     * Fimd
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function find($id)
    {
        if (!$this->permission) {
            throw new AuthorizationException();
        }

        if (!$this->permission->checkDetail()) {
            $msg = $this->permission->getMessage();
            throw new AuthorizationException($msg);
        }
        return $this->repository->find($id);
    }

    /**
     * Create
     *
     * @param array $input
     * @return boolean
     */
    public function create(array $input)
    {
        if (!$this->permission) {
            throw new AuthorizationException();
        }

        if (!$this->permission->checkCreate()) {
            $msg = $this->permission->getMessage();
            throw new AuthorizationException($msg);
        }

        $data = [];
        foreach ($this->repository->fillable as $value) {
            if (isset($input[$value])) {
                $data[$value] = $input[$value];
            }
        }
        if (!$this->createValidator->with($data)->passes()) {
            $this->errors = $this->createValidator->errors();
            throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $this->errors);
        }

        return $this->repository->create($data);
    }

    /**
     * createManyOfRow
     *
     * @param array $input
     * @return boolean
     */
    public function createManyOfRow(array $input)
    {
        if (!$this->permission) {
            throw new AuthorizationException();
        }

        if (!$this->permission->checkCreate()) {
            $msg = $this->permission->getMessage();
            throw new AuthorizationException($msg);
        }

        $data = [];
        foreach ($this->repository->fillable as $value) {
            if (isset($input[$value])) {
                $data[$value] = $input[$value];
            }
        }
        if (!$this->createValidator->with($data)->passes()) {
            $this->errors = $this->createValidator->errors();
            throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $this->errors);
        }

        return $this->repository->createManyOfRow($data);
    }


    /**
     * Update
     *
     * @param array $input
     * @return boolean
     */
    public function update(array $input)
    {
        if (!$this->permission) {
            throw new AuthorizationException();
        }

        if (!$this->permission->checkUpdate()) {
            $msg = $this->permission->getMessage();
            throw new AuthorizationException($msg);
        }

        $data = [];
        foreach ($this->repository->fillable as $value) {
            if (isset($input[$value])) {
                $data[$value] = $input[$value];
            }
        }
        if (!$this->updateValidator->with($data)->passes()) {
            $this->errors = $this->updateValidator->errors();
            throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $this->errors);
        }
        return $this->repository->update($data);
    }

    /**
     * Delete
     *
     * @return boolean
     */
    public function delete($id)
    {
        if (!$this->permission) {
            throw new AuthorizationException();
        }

        if (!$this->permission->checkDelete()) {
            $msg = $this->permission->getMessage();
            throw new AuthorizationException($msg);
        }

        return $this->repository->delete($id);
    }

    /**
     * Errors
     *
     * @return Illuminate\Support\MessageBag
     */
    public function errors()
    {
        $errors = $this->errors;

        $message_err = $this->repository->withErrors();

        return $errors ?? $message_err;
    }
}
