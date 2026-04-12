<?php

namespace App\Module\Portal\Validation\Module;

use Package\Validation\ValidableInterface;
use Package\Validation\Source\TeraValidator;

class ModuleCreateValidator extends TeraValidator implements ValidableInterface
{

    /**
     * Validation for creating a new User
     *
     * @var array
     */
    protected $rules = array(
        'title' => 'required',
        'url' => 'required',
        'code' => 'required|unique:ad_modules',
    );

    protected $message = array(
        'title.required' => 'Tên không thể để trống',
        'url.required' => 'URL không thể để trống',
        'code.required' => 'Mã module không thể để trống',
        'code.unique' => 'Mã module đã tồn tại'
    );
}
