<?php

namespace App\Module\Portal\Helpers;

use App\Models\Role;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;

class Helper
{

  public function includeAdapter(&$data, $includeField, $model, $includeId, $relationships = [])
  {
    $recordWithIdOne = $data->firstWhere($includeField, $includeId);

    if ($recordWithIdOne) {
      $data = $data->reject(function ($item) use ($includeField, $includeId) {
        return $item[$includeField] == $includeId;
      })->prepend($recordWithIdOne);
    } else {
      $includeData = $model::with($relationships)->where($includeField, $includeId)->first();
      $data->prepend($includeData);
    }

    return $data;
  }

  public function paginateCustom($data)
  {
    $page = request()->input('page', 1);
    $perPage = request()->input('limit', 10);
    $dataPaginated = new LengthAwarePaginator(
      $data->forPage($page, $perPage)->values(),
      $data->count(),
      $perPage,
      $page
    );

    $dataPaginated->setPath(request()->url());

    return $dataPaginated;
  }
}
