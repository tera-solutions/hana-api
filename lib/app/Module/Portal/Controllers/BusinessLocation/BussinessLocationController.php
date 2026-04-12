<?php

namespace App\Module\Portal\Controllers\BusinessLocation;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\BussinessLocationEntity;
use App\Module\Portal\Util\ModuleUtil;
use Exception;
use Illuminate\Http\Request;
use Package\Exception\HttpException;

class BussinessLocationController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $moduleUtil;
    protected $entity;
    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(
        BussinessLocationEntity $entity
    ) {
        $this->entity = $entity;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        try {
            $data = $this->entity->all($request);
            return $this->respondSuccess($data);
        } catch (HttpException $e) {
            $message = $e->getMessage();
            $errors = $e->getErrors();
            $code = $e->getStatusCode();
            return $this->respondWithError($message,   $errors,  $code);
        }
    }

    public function pushChanges(Request $request)
    {
        try {
            $location = $this->entity->syncDataLocal($request);
            if(!$location)  {
                    throw new HttpException("Đồng bộ không thành công");
            }
            $message = "Đồng bộ chi nhánh thành công";
            return $this->respondSuccess($location, $message);
        } catch (HttpException $e) {
            $message = $e->getMessage();
            $errors = $e->getErrors();
            $code = $e->getStatusCode();
            return $this->respondWithError($message,   $errors,  $code);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        try {
            $location = $this->entity->createData($request);
            $message = "Thêm chi nhánh thành công";
            return $this->respondSuccess($location, $message);
        } catch (HttpException $e) {
            $message = $e->getMessage();
            $errors = $e->getErrors();
            $code = $e->getStatusCode();
            return $this->respondWithError($message,   $errors,  $code);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function detail($id)
    {
        try {
            $location = $this->entity->find($id);
            return $this->respondSuccess($location);
        } catch (HttpException $e) {
            $message = $e->getMessage();
            $errors = $e->getErrors();
            $code = $e->getStatusCode();
            return $this->respondWithError($message,   $errors,  $code);
        }
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
        try {
            $location = $this->entity->updateData($request, $id);
            $message = "Cập nhật chi nhánh thành công";
            return $this->respondSuccess($location, $message);
        } catch (HttpException $e) {
            $message = $e->getMessage();
            $errors = $e->getErrors();
            $code = $e->getStatusCode();
            return $this->respondWithError($message,   $errors,  $code);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        try {
            $location = $this->entity->delete($id);
            $message = "Xóa chi nhánh thành công";
            return $this->respondSuccess($location, $message);
        } catch (HttpException $e) {
            $message = $e->getMessage();
            $errors = $e->getErrors();
            $code = $e->getStatusCode();
            return $this->respondWithError($message,   $errors,  $code);
        }
    }

    public function importData(Request $request)
    {
        try {
            $result = $this->entity->importData($request);
            $message = "Nhập liệu chi nhánh thành công";
            return $this->respondSuccess($result, $message);
        } catch (HttpException $e) {
            $message = $e->getMessage();
            $errors = $e->getErrors();
            $code = $e->getStatusCode();
            return $this->respondWithError($message,   $errors,  $code);
        }
    }
}
