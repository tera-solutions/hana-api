<?php

namespace App\Modules\System\Package\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Package\Actions\ListPackageAction;
use App\Modules\System\Package\Http\Resources\PackageResource;
use Illuminate\Http\Request;

/**
 * @group System - Package
 *
 * Subscription package catalog.
 *
 * @authenticated
 */
class PackageController extends Controller
{
    public function list(Request $request, ListPackageAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), PackageResource::class);
    }
}
