<?php

namespace App\Modules\System\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Setting\Actions\CreateSettingAction;
use App\Modules\System\Setting\Actions\DeleteSettingAction;
use App\Modules\System\Setting\Actions\GetSettingAction;
use App\Modules\System\Setting\Actions\ListSettingAction;
use App\Modules\System\Setting\Actions\UpdateSettingAction;
use App\Modules\System\Setting\Actions\UpsertSettingAction;
use App\Modules\System\Setting\Http\Requests\CreateSettingRequest;
use App\Modules\System\Setting\Http\Requests\UpdateSettingRequest;
use App\Modules\System\Setting\Http\Requests\UpsertSettingRequest;
use App\Modules\System\Setting\Http\Resources\SettingResource;
use Illuminate\Http\Request;

/**
 * @group System - Setting
 *
 * Manage business-scoped application settings (key/value).
 *
 * @authenticated
 */
class SettingController extends Controller
{
    public function list(Request $request, ListSettingAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), SettingResource::class);
    }

    public function detail($id, GetSettingAction $action)
    {
        return $this->respondSuccess(new SettingResource($action->handle($id)));
    }

    public function create(CreateSettingRequest $request, CreateSettingAction $action)
    {
        $setting = $action->handle($request->validated());

        return $this->respondSuccess(new SettingResource($setting), 'Tạo cài đặt thành công.');
    }

    public function update(UpdateSettingRequest $request, $id, UpdateSettingAction $action)
    {
        $setting = $action->handle($id, $request->validated());

        return $this->respondSuccess(new SettingResource($setting), 'Cập nhật cài đặt thành công.');
    }

    public function upsert(UpsertSettingRequest $request, UpsertSettingAction $action)
    {
        $setting = $action->handle($request->validated());

        return $this->respondSuccess(new SettingResource($setting), 'Lưu cài đặt thành công.');
    }

    public function delete($id, DeleteSettingAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa cài đặt thành công.');
    }
}
