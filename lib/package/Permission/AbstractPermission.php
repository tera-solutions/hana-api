<?php

namespace Package\Permission;

use Illuminate\Support\Facades\Auth;

abstract class AbstractPermission
{

    /**
     * Validator
     *
     * @var object
     */
    protected $permission;

    protected $errors;

    protected $keys;

    protected $messages;

    public function checkKey($value)
    {
        return true;

        if (!$value) {
            return false;
        }

        // get key from entity
        $get_key = isset($this->keys[$value]) ? $this->keys[$value] : null;

        // Check permission key
        if (!$get_key) {
            $this->errors =  isset($this->messages[$get_key]) ? $this->messages[$get_key] : null;
            return false;
        }

        $user_id = Auth::guard('api')->user()->user_id;
        $role_id = Auth::guard('api')->user()->role_id;

        if (!$role_id) {
            $this->errors =  isset($this->messages[$get_key]) ? $this->messages[$get_key] : null;
            return false;
        }

        return true;
    }

    /**
     * Check permission for list
     */
    public function checkList()
    {
        return $this->checkKey("list");
    }

    /**
     * Check permission for list
     */
    public function checkDetail()
    {
        return $this->checkKey("detail");
    }

    /**
     * Check permission for list
     */
    public function checkCreate()
    {
        return $this->checkKey("create");
    }

    /**
     * Check permission for list
     */
    public function checkUpdate()
    {
        return $this->checkKey("update");
    }

    /**
     * Check permission for list
     */
    public function checkDelete()
    {
        return $this->checkKey("delete");
    }
    /**
     * Get message
     */
    public function getMessage()
    {
        return $this->errors;
    }
}
