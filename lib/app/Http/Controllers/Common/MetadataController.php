<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Support\Metadata\MetadataRegistry;

/**
 * @group Core - Metadata
 *
 * Frontend bootstrap data: every model enumeration (status, type, gender, …) as
 * `{ value, label }` lists, grouped by domain. Fetch once after login to populate
 * dropdowns, status badges and filters without hardcoding option lists.
 *
 * @authenticated
 */
class MetadataController extends Controller
{
    /**
     * Init metadata
     *
     * Returns the full enum catalog. Labels are in Vietnamese.
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "shared": {
     *       "gender": [{"value": "male", "label": "Nam"}, {"value": "female", "label": "Nữ"}, {"value": "other", "label": "Khác"}]
     *     },
     *     "education": {
     *       "enrollment_status": [
     *         {"value": "pending", "label": "Chờ xác nhận"},
     *         {"value": "studying", "label": "Đang học"},
     *         {"value": "suspended", "label": "Bảo lưu"},
     *         {"value": "transferred", "label": "Chuyển lớp"},
     *         {"value": "completed", "label": "Hoàn thành"},
     *         {"value": "cancelled", "label": "Hủy đăng ký"},
     *         {"value": "refunded", "label": "Hoàn phí"}
     *       ]
     *     }
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function index()
    {
        return $this->respondSuccess(MetadataRegistry::all());
    }
}
