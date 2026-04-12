<?php

namespace App\Module\Portal\Controllers\Module;

use App\Helpers\Activity;
use App\Http\Controllers\Controller;
use App\Module\Portal\Model\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Module\Portal\Entity\ModuleEntity;

class ApiController extends Controller
{
    protected $module;
    public function __construct(ModuleEntity $module)
    {
        $this->module = $module;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $data = $this->module->all($request);
        return $this->respondSuccess($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $data = $request->all();
        $result = $this->module->create($data);
        return $this->respondSuccess($result);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\contract $module
     * @return \Illuminate\Http\Response
     */
    public function detail($id)
    {
        $data = $this->module->find($id);
        if (!$data) {
            return $this->respondWithError("Không tìm thấy dữ liệu", [], 500);
        }
        return $this->respondSuccess($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();
        $input['id'] = $id;
        $module = $this->module->update($input);
        $message = "Cập nhật Module thành công";
        return $this->respondSuccess($module, $message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $pagetablecolumn = $this->module->find($id);
        if (!$pagetablecolumn) {
            return $this->respondWithError("Không tìm thấy dữ liệu", [], 500);
        }

        $result = $this->module->delete($id);

        return $this->respondSuccess($result);
    }
}
