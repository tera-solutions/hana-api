<?php

namespace App\Module\Portal\Controllers\ActivityLog;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\ActivityLogEntity;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    protected $activityLog;

    public function __construct(ActivityLogEntity $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $data = $this->activityLog->all($request);
        return $this->respondSuccess($data);
    }
}
