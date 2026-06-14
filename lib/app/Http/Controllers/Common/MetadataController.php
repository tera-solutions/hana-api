<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Support\Metadata\MetadataRegistry;

/**
 * @group Core - Metadata
 *
 * Frontend bootstrap data: every model enumeration (status, type, gender, …) as
 * `{ key, value, label }` lists, grouped by domain. `key` is the UPPER_SNAKE enum
 * identifier (stable), `value` the wire value, `label` the Vietnamese display text.
 * Fetch once after login to populate dropdowns, status badges and filters without
 * hardcoding option lists.
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
     *       "gender": [{"key": "MALE", "value": "male", "label": "Nam"}, {"key": "FEMALE", "value": "female", "label": "Nữ"}, {"key": "OTHER", "value": "other", "label": "Khác"}]
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
