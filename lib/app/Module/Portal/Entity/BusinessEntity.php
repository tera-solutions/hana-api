<?php

namespace App\Module\Portal\Entity;


use App\Models\Role;
use App\Module\Portal\Helpers\SyncPermissionRole;
use App\Module\Portal\Model\Business;
use App\Module\Portal\Model\BusinessService;
use App\Module\Portal\Model\Employee;
use App\Module\Portal\Model\Service;
use App\Module\Portal\Repository\MemberRepository;
use GuzzleHttp\Client;
use Package\Entity\AbstractEntity;
use Package\Entity\EntityInterface;
use Package\Exception\ValidationException;
use App\Module\Portal\Repository\BusinessRepository;
use App\Module\Portal\Permission\BusinessPermission;
use App\Module\Portal\Validation\Business\BusinessCreateValidation;
use App\Module\Portal\Validation\Business\BusinessUpdateValidation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Package\Exception\AuthorizationException;
use Package\Exception\HttpException;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class BusinessEntity extends AbstractEntity implements EntityInterface
{

    /**
     * @var BusinessRepository
     */
    protected $repository;

    protected $memberRepository;
    /**
     * @var BusinessCreateValidation
     */
    protected $createValidator;

    /**
     * @var BusinessUpdateValidation
     *
     * */
    protected $updateValidator;

    protected $syncRolePermission;


    /**
     * @var
     */
    protected $errors;

    /**
     * @var BusinessPermission
     */
    protected $permission;

    /**
     * RegisterEntity constructor.
     * @param BusinessRepository $repository
     * @param BusinessUpdateValidation $updateValidator
     */
    public function __construct(
        BusinessRepository       $repository,
        SyncPermissionRole       $syncRolePermission,
        BusinessCreateValidation $createValidator,
        BusinessUpdateValidation $updateValidator,
        BusinessPermission       $permission,
        MemberRepository         $memberRepository
    ) {
        $this->repository = $repository;
        $this->createValidator = $createValidator;
        $this->updateValidator = $updateValidator;
        $this->permission = $permission;
        $this->memberRepository = $memberRepository;
        $this->syncRolePermission = $syncRolePermission;
    }

    public function create($data)
    {
        try {
            return DB::transaction(function () use ($data) {
                if (!$this->permission) {
                    throw new AuthorizationException();
                }

                if (!$this->permission->checkCreate()) {
                    $msg = $this->permission->getMessage();
                    throw new AuthorizationException($msg);
                }

                if (!$this->createValidator->with($data)->passes()) {
                    $this->errors = $this->createValidator->errors();
                    throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $this->errors);
                }
                if (isset($data['status'])) {
                    $data['status'] = $data['status'];
                }
                $result = $this->repository->create($data);
                return $result;
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function update($data)
    {
        try {
            return DB::transaction(function () use ($data) {
                if (!$this->permission) {
                    throw new AuthorizationException();
                }

                if (!$this->permission->checkUpdate()) {
                    $msg = $this->permission->getMessage();
                    throw new AuthorizationException($msg);
                }

                if (!$this->updateValidator->with($data)->passes()) {
                    $this->errors = $this->updateValidator->errors();
                    throw new ValidationException("Vui lòng nhập lại dữ liệu", 422, $this->errors);
                }

                $result = $this->repository->update($data);
                return $result;
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function register($request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $user = Auth::guard('api')->user();
                $user_id = $user->id;


                $data = $request->all();
                $data['status'] = 'no_activated';
                $data['owner_email'] = $user->email;
                $autoActive = env('AUTO_ACTIVE');

                if ($autoActive == 1) {
                    $data['is_active'] = 1;
                    $data['status'] = 'is_active';
                    $data['register_time'] = now()->format('Y-m-d');
                }

                $register = Business::updateOrCreate([
                    'owner_email' => $data['owner_email']
                ], $data);

                DB::commit();

                if ($register) {
                    $this->createBusinessDefault($register->id);


                    $role = $this->createRoleDefault($register->id);

                    DB::commit();
                    $user = User::where('id', $user_id)->first();

                    if ($user) {
                        $user->type = 'owner';
                        $user->role_id = $role->id;
                        $user->business_id = $register->id;
                        $user->save();
                        $this->addServicePackageTrialDefault($register->id);
                        $this->syncRolePermission->syncPermissionsBusiness($register->id, [], true);
                        if ($autoActive == 1) {
                            $url = env('CRM_URL_EMPLOYEE') . "/api/hrm/employee/create-when-create-user";
                            $token = $this->memberRepository->getCurrentBearerToken();
                            $this->callAPICreateEmployeeAfterCreateUser($url, $token, $user->id, $register->id);
                        }
                    }
                    return $user;
                }
                return $register;
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function callAPICreateEmployeeAfterCreateUser($apiUrl, $bearerToken, $userId, $business_id)
    {
        try {
            $client = new Client();
            $params = [
                'user_id' => $userId,
                'is_default' => 1,
                'business_id' => $business_id
            ];
            $response = $client->get($apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $bearerToken,
                    'Accept' => 'application/json',
                ],
                'query' => $params,
            ]);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function createRoleDefault($business_id)
    {
        return Role::updateOrCreate([
            'business_id' => $business_id,
            'type' => 'user',
            'is_default' => 1,
        ], [
            'business_id' => $business_id,
            'title' => 'Mặc định',
            'code_guard' => 'default',
            'code' => 'default',
            'type' => 'user',
            'is_default' => 1,
            'created_at' => now(),
        ]);
    }

    public function addServicePackageTrialDefault($business_id)
    {
        $getTrialDefault = Service::with(['packages'])->where('is_trial_default', 1)->first();
        if ($getTrialDefault) {
            if (!empty($getTrialDefault->packages)) {
                $arrInsert = [];
                $checkHaveOrNo = BusinessService::where('business_id', $business_id)
                    ->where('service_id', $getTrialDefault->id)
                    ->whereIn('package_id', array_column(collect($getTrialDefault->packages)->toArray(), "id"))
                    ->first();
                if (!$checkHaveOrNo) {
                    foreach ($getTrialDefault->packages as $key => $value) {
                        $arrInsert[] = [
                            "business_id" => $business_id,
                            "service_id" => $getTrialDefault->id,
                            "package_id" => $value->id,
                            "date_active" => now()->format('Y-m-d'),
                            "date_expired" => now()->addMonths(1)->format('Y-m-d'),
                            "status" => "is_active",
                            "service_type" => "trial",
                            "time" => 1,
                            "created_at" => now()
                        ];
                    }
                }

                if (!empty($arrInsert)) {
                    BusinessService::insert($arrInsert);
                }
            }
        }
    }

    private function createBusinessDefault($business_id)
    {
        $token = $this->memberRepository->getCurrentBearerToken();
        $url = env('AUTH_CURL') . '/api/auth/business-default/' . $business_id;
        return Http::withHeaders([
            'Accept' => '*/*',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/json',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-site',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'authorization' => 'Bearer ' . $token,
            'device-code' => 'yfUW52kpfUKH',
        ])
            ->post($url);
    }

    public function save($request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $data = $request->all();
                $user = Auth::guard('api')->user();
                if (!$user->business_id) {
                    throw new HttpException("Tài khoản này chưa phải là doanh nghiệp !");
                }
                $business = Business::where('id', $user->business_id)->first();
                if (!$business) {
                    throw new HttpException("Không tìm thấy data");
                }

                $getOwner = User::where('type', 'owner')->where('business_id', $business->id)->first();

                if ($getOwner) {
                    $getOwner->update([
                        'phone' => $data['owner_phone'] ?? null,
                        'full_name' => $data['owner_name'] ?? null,
                        'department' => $data['owner_department'] ?? null,
                        'job_title' => $data['owner_job_title'] ?? null,
                    ]);
                }

                $business->update($data);
                return $business;
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function getInfo()
    {
        try {
            return DB::transaction(function () {
                $user = Auth::guard('api')->user();
                if (!$user->business_id) {
                    throw new HttpException("Tài khoản này chưa phải là doanh nghiệp !");
                }
                $business = Business::with(['owner'])->where('id', $user->business_id)->first();

                if (!$business) {
                    throw new HttpException("Doanh nghiệp không tồn tại !");
                }

                return $business;
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }
}
