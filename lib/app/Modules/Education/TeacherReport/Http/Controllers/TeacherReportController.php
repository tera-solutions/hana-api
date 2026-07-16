<?php

namespace App\Modules\Education\TeacherReport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\TeacherReport\Actions\TeacherReportSummaryAction;
use Illuminate\Http\Request;

/**
 * @group Education - TeacherReport
 *
 * Teaching-activity report for the authenticated teacher's own classes.
 *
 * @authenticated
 */
class TeacherReportController extends Controller
{
    /**
     * @queryParam class_id integer Filter to a single class. Example: 1
     * @queryParam date_from date Range start (Y-m-d). Example: 2026-05-01
     * @queryParam date_to date Range end (Y-m-d). Example: 2026-05-15
     */
    public function summary(Request $request, TeacherReportSummaryAction $action)
    {
        return $this->respondSuccess($action->handle($request->all()));
    }
}
