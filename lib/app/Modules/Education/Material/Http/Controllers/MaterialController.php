<?php

namespace App\Modules\Education\Material\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Material\Actions\AddMaterialVersionAction;
use App\Modules\Education\Material\Actions\AttachMaterialAction;
use App\Modules\Education\Material\Actions\CreateMaterialAction;
use App\Modules\Education\Material\Actions\DeleteMaterialAction;
use App\Modules\Education\Material\Actions\DetachMaterialAction;
use App\Modules\Education\Material\Actions\GetMaterialAction;
use App\Modules\Education\Material\Actions\ListMaterialAction;
use App\Modules\Education\Material\Actions\ListMaterialMappingAction;
use App\Modules\Education\Material\Actions\PublishMaterialAction;
use App\Modules\Education\Material\Actions\RollbackMaterialVersionAction;
use App\Modules\Education\Material\Actions\UpdateMaterialAction;
use App\Modules\Education\Material\Http\Requests\AddMaterialVersionRequest;
use App\Modules\Education\Material\Http\Requests\AttachMaterialRequest;
use App\Modules\Education\Material\Http\Requests\CreateMaterialRequest;
use App\Modules\Education\Material\Http\Requests\RollbackMaterialVersionRequest;
use App\Modules\Education\Material\Http\Requests\UpdateMaterialRequest;
use App\Modules\Education\Material\Http\Resources\MaterialMappingResource;
use App\Modules\Education\Material\Http\Resources\MaterialResource;
use App\Modules\Education\Material\Http\Resources\MaterialVersionResource;
use Illuminate\Http\Request;

/**
 * @group Education - Material
 *
 * Central learning-resource library: documents, versioning and links to courses,
 * lesson plans, lessons and assignments (material.md).
 *
 * @authenticated
 */
class MaterialController extends Controller
{
    /**
     * List materials
     */
    public function list(Request $request, ListMaterialAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), MaterialResource::class);
    }

    /**
     * Material detail
     *
     * @urlParam id integer required The material ID. Example: 1
     */
    public function detail($id, GetMaterialAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'material' => new MaterialResource($result['material']),
            'usage' => $result['usage'],
        ]);
    }

    /**
     * Create material
     */
    public function create(CreateMaterialRequest $request, CreateMaterialAction $action)
    {
        $material = $action->handle($request->validated());

        return $this->respondSuccess(new MaterialResource($material), 'Tạo tài liệu thành công.');
    }

    /**
     * Update material
     *
     * @urlParam id integer required The material ID. Example: 1
     */
    public function update(UpdateMaterialRequest $request, $id, UpdateMaterialAction $action)
    {
        $material = $action->handle($id, $request->validated());

        return $this->respondSuccess(new MaterialResource($material), 'Cập nhật tài liệu thành công.');
    }

    /**
     * Upload a new version
     *
     * Real file storage is not implemented; the file is referenced by id + metadata.
     *
     * @urlParam id integer required The material ID. Example: 1
     */
    public function upload(AddMaterialVersionRequest $request, $id, AddMaterialVersionAction $action)
    {
        $version = $action->handle($id, $request->validated());

        return $this->respondSuccess(new MaterialVersionResource($version), 'Tải lên phiên bản mới thành công.');
    }

    /**
     * Roll back to a previous version
     *
     * @urlParam id integer required The material ID. Example: 1
     */
    public function rollback(RollbackMaterialVersionRequest $request, $id, RollbackMaterialVersionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()['version']),
            'Khôi phục phiên bản thành công.',
            fn ($material) => new MaterialResource($material),
        );
    }

    /**
     * Publish material
     *
     * @urlParam id integer required The material ID. Example: 1
     */
    public function publish($id, PublishMaterialAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id),
            'Xuất bản tài liệu thành công.',
            fn ($material) => new MaterialResource($material),
        );
    }

    /**
     * Delete material
     *
     * @urlParam id integer required The material ID. Example: 1
     */
    public function delete($id, DeleteMaterialAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa tài liệu thành công.');
    }

    /**
     * Attach material to an entity
     *
     * @urlParam id integer required The material ID. Example: 1
     */
    public function attach(AttachMaterialRequest $request, $id, AttachMaterialAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()),
            'Liên kết tài liệu thành công.',
            fn ($mapping) => new MaterialMappingResource($mapping),
        );
    }

    /**
     * Detach a material link
     *
     * @urlParam id integer required The mapping ID. Example: 1
     */
    public function detach($id, DetachMaterialAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Gỡ liên kết tài liệu thành công.');
    }

    /**
     * List where a material is used
     *
     * @urlParam id integer required The material ID. Example: 1
     */
    public function mappings($id, ListMaterialMappingAction $action)
    {
        return $this->respondSuccess(MaterialMappingResource::collection($action->handle($id)));
    }
}
