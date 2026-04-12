<?php

namespace App\Module\Portal\Controllers\SyncLocalData;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\SyncLocalDataEntity;
use App\Module\Portal\Util\ModuleUtil;
use Exception;
use Illuminate\Http\Request;
use Package\Exception\HttpException;

class SyncLocalDataController extends Controller
{

    public function __construct(
        SyncLocalDataEntity $entity
    ) {
        $this->entity = $entity;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function pullChanges(Request $request)
    {
        try {
            $data = $this->entity->pullChanges($request);
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
            $result = $this->entity->pushChanges($request);
            if(!$result)  {
                    throw new HttpException("Đồng bộ không thành công");
            }
            $message = "Đồng bộ thành công";
            return $this->respondSuccess($result, $message);
        } catch (HttpException $e) {
            $message = $e->getMessage();
            $errors = $e->getErrors();
            $code = $e->getStatusCode();
            return $this->respondWithError($message,   $errors,  $code);
        }
    }
}
