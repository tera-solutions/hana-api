<?php

namespace App\Modules\Finance\SubscriptionPackage\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\SubscriptionPackage\Actions\CreateSubscriptionPackageAction;
use App\Modules\Finance\SubscriptionPackage\Actions\DeleteSubscriptionPackageAction;
use App\Modules\Finance\SubscriptionPackage\Actions\GetSubscriptionPackageAction;
use App\Modules\Finance\SubscriptionPackage\Actions\ListSubscriptionPackageAction;
use App\Modules\Finance\SubscriptionPackage\Actions\SetDiscountRulesSubscriptionPackageAction;
use App\Modules\Finance\SubscriptionPackage\Actions\SummarySubscriptionPackageAction;
use App\Modules\Finance\SubscriptionPackage\Actions\ToggleSubscriptionPackageAction;
use App\Modules\Finance\SubscriptionPackage\Actions\UpdateSubscriptionPackageAction;
use App\Modules\Finance\SubscriptionPackage\Actions\UsagesSubscriptionPackageAction;
use App\Modules\Finance\SubscriptionPackage\Http\Requests\CreateSubscriptionPackageRequest;
use App\Modules\Finance\SubscriptionPackage\Http\Requests\SetDiscountRulesRequest;
use App\Modules\Finance\SubscriptionPackage\Http\Requests\UpdateSubscriptionPackageRequest;
use App\Modules\Finance\SubscriptionPackage\Http\Resources\SubscriptionPackageResource;
use Illuminate\Http\Request;

/**
 * @group Finance - Subscription Package
 *
 * Tuition subscription packages (theo buổi/tháng/kỳ/tùy chỉnh), applied when
 * enrolling a student (teacher-app-081/082).
 *
 * @authenticated
 */
class SubscriptionPackageController extends Controller
{
    /**
     * List subscription packages
     *
     * @queryParam search string Match package name. Example: Gói tháng
     * @queryParam status string Filter: active|inactive. Example: active
     * @queryParam type string Filter: session|month|term|custom. Example: month
     */
    public function list(Request $request, ListSubscriptionPackageAction $action, SummarySubscriptionPackageAction $summaryAction)
    {
        $paginator = $action->handle($request->all());

        return $this->respondSuccess([
            'summary' => $summaryAction->handle(),
            'items' => SubscriptionPackageResource::collection($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Subscription package detail
     *
     * @urlParam id integer required The package ID. Example: 1
     */
    public function detail($id, GetSubscriptionPackageAction $action)
    {
        return $this->respondSuccess(new SubscriptionPackageResource($action->handle($id)));
    }

    /**
     * Create subscription package (quick add)
     */
    public function create(CreateSubscriptionPackageRequest $request, CreateSubscriptionPackageAction $action)
    {
        return $this->respondSuccess(
            new SubscriptionPackageResource($action->handle($request->validated())),
            'Tạo gói đăng ký thành công.',
        );
    }

    /**
     * Update subscription package
     *
     * @urlParam id integer required The package ID. Example: 1
     */
    public function update(UpdateSubscriptionPackageRequest $request, $id, UpdateSubscriptionPackageAction $action)
    {
        return $this->respondSuccess(
            new SubscriptionPackageResource($action->handle($id, $request->validated())),
            'Cập nhật gói đăng ký thành công.',
        );
    }

    /**
     * Toggle active/inactive status
     *
     * @urlParam id integer required The package ID. Example: 1
     */
    public function toggle($id, ToggleSubscriptionPackageAction $action)
    {
        return $this->respondSuccess(new SubscriptionPackageResource($action->handle($id)), 'Cập nhật trạng thái thành công.');
    }

    /**
     * Delete subscription package
     *
     * @urlParam id integer required The package ID. Example: 1
     */
    public function delete($id, DeleteSubscriptionPackageAction $action)
    {
        return $this->tryRespond(function () use ($id, $action) {
            $action->handle($id);

            return null;
        }, 'Xóa gói đăng ký thành công.');
    }

    /**
     * Students currently using this package
     *
     * @urlParam id integer required The package ID. Example: 1
     */
    public function usages($id, UsagesSubscriptionPackageAction $action)
    {
        return $this->respondSuccess($action->handle($id));
    }

    /**
     * Replace the package's discount rules
     *
     * @urlParam id integer required The package ID. Example: 1
     */
    public function setDiscountRules(SetDiscountRulesRequest $request, $id, SetDiscountRulesSubscriptionPackageAction $action)
    {
        return $this->respondSuccess(
            new SubscriptionPackageResource($action->handle($id, $request->validated('rules'))),
            'Cập nhật quy tắc giảm giá thành công.',
        );
    }
}
