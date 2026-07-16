<?php

namespace App\Modules\System\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Package\Http\Resources\PackageResource;
use App\Modules\System\Package\Services\PackageService;
use App\Modules\System\Superadmin\Http\Requests\CreatePackageRequest;
use App\Modules\System\Superadmin\Http\Requests\UpdatePackageRequest;
use Illuminate\Http\Request;

/**
 * @group System - Superadmin / Packages
 *
 * Manage the subscription plan catalog (price, quota limits, visibility).
 * Restricted to superadmins.
 *
 * @authenticated
 */
class PackageController extends Controller
{
    /**
     * List packages
     *
     * Includes inactive/internal plans (e.g. trial), unlike the tenant-facing
     * catalog which only returns active ones.
     *
     * @queryParam search string Search by name or code. Example: Basic
     * @queryParam is_active boolean Filter by visibility. Example: true
     */
    public function list(Request $request, PackageService $service)
    {
        return $this->respondPaginated($service->adminPaginate($request->all()), PackageResource::class);
    }

    /**
     * Package detail
     *
     * @urlParam id integer required The package id. Example: 1
     */
    public function detail($id, PackageService $service)
    {
        return $this->respondSuccess(new PackageResource($service->find((int) $id)));
    }

    /**
     * Create package
     */
    public function create(CreatePackageRequest $request, PackageService $service)
    {
        $package = $service->create($request->validated());

        return $this->respondSuccess(new PackageResource($package), 'Tạo gói dịch vụ thành công.');
    }

    /**
     * Update package
     *
     * @urlParam id integer required The package id. Example: 1
     */
    public function update(UpdatePackageRequest $request, $id, PackageService $service)
    {
        $package = $service->update((int) $id, $request->validated());

        return $this->respondSuccess(new PackageResource($package), 'Cập nhật gói dịch vụ thành công.');
    }

    /**
     * Activate package (list it to tenants)
     *
     * @urlParam id integer required The package id. Example: 1
     */
    public function activate($id, PackageService $service)
    {
        return $this->respondSuccess(
            new PackageResource($service->setActive((int) $id, true)),
            'Đã kích hoạt gói dịch vụ.',
        );
    }

    /**
     * Deactivate package (hide from tenants)
     *
     * @urlParam id integer required The package id. Example: 1
     */
    public function deactivate($id, PackageService $service)
    {
        return $this->respondSuccess(
            new PackageResource($service->setActive((int) $id, false)),
            'Đã ẩn gói dịch vụ.',
        );
    }
}
