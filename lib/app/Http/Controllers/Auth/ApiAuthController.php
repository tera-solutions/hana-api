<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\SyncPermissionRole;
use App\Helpers\Task;
use App\Http\Controllers\Controller;
use App\Jobs\MailRegister as JobsMailRegister;
use App\Jobs\SendMailAccountActivation;
use App\Jobs\SendMailInformationAccount;
use App\Jobs\SendMailResetPassword;
use App\Jobs\SendMailVerifyOTP;
use App\Models\AccessToken;
use App\Models\Business;
use App\Models\BusinessCRM;
use App\Models\BusinessService;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\MailConfig;
use App\Models\Module;
use App\Models\ModulePermission;
use App\Models\OTP;
use App\Models\Role;
use App\Models\Service;
use App\Models\Session;
use App\Models\StockCRM;
use App\Models\Token;
use App\Models\User;
use App\Modules\System\ActivityLog\Support\ActivityLogger;
use Exception;
use Google_Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Package\Exception\HttpException;

/**
 * @group Core - Authentication
 *
 * Login, registration, OTP, password reset and related account flows.
 * Most endpoints are public; ones marked @authenticated require a bearer token.
 */
class ApiAuthController extends Controller
{
    public $syncRolePermission;

    public function __construct(SyncPermissionRole $syncRolePermissionHelper)
    {
        $this->syncRolePermission = $syncRolePermissionHelper;
    }

    /**
     * Login
     *
     * Authenticates a user and returns an access token. Requires a `device-code` header
     * (obtain one from `POST /api/auth/device/init`).
     *
     * @header Device-code {your-device-code}
     *
     * @bodyParam username string required The username. Example: super
     * @bodyParam password string required The password. Example: 12345678
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Đăng nhập thành công !",
     *   "data": {
     *     "verify_auth": 0,
     *     "user": {"id": 1, "username": "super", "full_name": "John Doe", "email": "super@example.com"},
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
     *     "access_id": "a1b2c3"
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Wrong credentials" {
     *   "success": false,
     *   "msg": {"field": "password", "message": "Mật khẩu không hợp lệ"},
     *   "data": null,
     *   "code": 500,
     *   "errors": []
     * }
     */
    public function login(Request $request)
    {
        DB::beginTransaction();
        try {
            $input = $request;

            $validator = Validator::make($request->all(), []);
            $device_code = $request->header('device-code');

            if ($validator->fails()) {
                $message = 'Vui lòng nhập đủ thông tin';
                $errors = $validator->errors()->all();
                DB::rollBack();

                return $this->respondWithError($message, $errors, 422);
            }

            if (! $device_code) {
                $message = 'Không tìm thấy thiết bị';
                DB::rollBack();

                return $this->respondWithError($message, [], 422);
            }

            $query = "(username = '$input->username')";

            $user = User::whereRaw(DB::raw($query))
                ->first();

            if ($user) {
                $pass = $input->password;
                if (Hash::check($pass, $user->password)) {
                    $dataUser = User::select([
                        'id',
                        'username',
                        'full_name',
                        'email',
                        'avatar',
                        'status',
                        'code',
                        'is_admin',
                    ])
                        ->find($user->id);

                    if (! $user->is_active) {
                        return $this->respondWithError('Tài khoản đã ngưng hoạt động hoặc chưa được kích hoạt !', [], 501);
                    }

                    $response = $this->createToken($user);
                    DB::commit();
                    $this->logAuthEvent('login', 'success', $user->id, "Đăng nhập thành công: {$user->username}");

                    return $this->respondSuccess($response, 'Đăng nhập thành công !');
                } else {
                    $message = 'Mật khẩu không hợp lệ';
                    DB::rollBack();
                    $this->logAuthEvent('login', 'failed', $user->id, "Đăng nhập thất bại (sai mật khẩu): {$input->username}");

                    return $this->respondWithError([
                        'field' => 'password',
                        'message' => $message,
                    ], [], 500);
                }
            } else {
                $message = 'Tài khoản không tồn tại';
                DB::rollBack();
                $this->logAuthEvent('login', 'failed', null, "Đăng nhập thất bại (tài khoản không tồn tại): {$input->username}");

                return $this->respondWithError([
                    'field' => 'username',
                    'message' => $message,
                ], [], 500);
            }
        } catch (Exception $err) {
            DB::rollBack();

            return $this->respondWithError($err->getMessage(), [], 403);
        }
    }

    //
    private function sendOTP($data, $dataUser, $user)
    {
        $mailConfig = MailConfig::where('type', 'system')->first();
        $OTP = substr(rand(), 0, 6);
        OTP::updateOrCreate([
            'user_id' => $dataUser->id,
        ], [
            'user_id' => $dataUser->id,
            'otp_code' => $OTP,
            'expired' => now()->addMinutes(5),
            'created_at' => now(),
            'count' => DB::raw('count + 1'),
            'count_wrong' => 0,
        ]);
        SendMailVerifyOTP::dispatch($dataUser, $OTP, $mailConfig);
        $data['verify_auth'] = $user->verify_auth;
        $data['user']['id'] = $dataUser->id;

        return $data;
    }

    private function createToken($user)
    {
        $token = $user->createToken('Tera Auth Private key');
        $user->save();

        return [
            'verify_auth' => $user->verify_auth,
            'user' => $user,
            'token' => $token->accessToken,
            'access_id' => $token->accessTokenId ?? null,
        ];
    }

    /**
     * Record an authentication event in the system audit trail (spec 028). Called
     * after the request transaction commits/rolls back so failed-login attempts are
     * still captured.
     */
    private function logAuthEvent(string $action, string $status, ?int $userId, string $description): void
    {
        ActivityLogger::log([
            'module' => 'system',
            'entity' => 'User',
            'entity_id' => $userId,
            'action' => $action,
            'status' => $status,
            'user_id' => $userId,
            'description' => $description,
        ]);
    }

    public function generateToken($length = 32)
    {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }

    /**
     * Verify OTP
     *
     * Verifies a one-time code and issues an access token. Locks the account after 5 wrong attempts.
     *
     * @bodyParam user_id integer required The user ID. Example: 1
     * @bodyParam otp_code string required The 6-digit OTP. Example: 123456
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "verify_auth": 1,
     *     "user": {"id": 1, "username": "super"},
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
     *     "access_id": 123
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function verifyOTP(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $user = User::where('id', $request->user_id)->first();
                if (! $user) {
                    $this->logAuthEvent('login', 'failed', null, "Xác thực OTP thất bại (tài khoản không tồn tại): user_id={$request->user_id}");

                    return $this->respondWithError('Tài khoản không tồn tại !', [], 500);
                }
                $check = OTP::where('user_id', $request->user_id)->first();
                if ($check) {
                    if ($user->time_block) {
                        if (now() > $user->time_block) {
                            $user->time_block = null;
                            $user->save();
                            $check->count_wrong = 0;
                        }
                    }
                    if ($check->count_wrong >= 5) {
                        $timeBlock = now()->addHours(1);
                        $userBlock = User::where('id', $check->user_id)->first();
                        if ($userBlock) {
                            $userBlock->time_block = $timeBlock;
                            $userBlock->save();
                        }

                        $this->logAuthEvent('login', 'failed', $user->id, "Xác thực OTP thất bại (tài khoản bị khóa): {$user->username}");

                        return $this->respondWithError('Đã quá số lần nhập sai mã xác thực, tài khoản bị khóa 1 giờ!', [], 501);
                    }
                    if ($check->otp_code == $request->otp_code) {
                        if (now() > $check->expired) {
                            $this->logAuthEvent('login', 'failed', $user->id, "Xác thực OTP thất bại (mã hết hạn): {$user->username}");

                            return $this->respondWithError('Mã OTP đã hết hạn !', [], 501);
                        } else {
                            if ($user->is_active == 1) {
                                $token = $user->createToken('Tera Auth Private key');
                                $user->save();
                                $response = [
                                    'verify_auth' => $user->verify_auth,
                                    'user' => $user,
                                    'token' => $token->accessToken,
                                    'access_id' => isset($token->token->id) ? $token->token->id : null,
                                ];
                                $check->delete();

                                $this->logAuthEvent('login', 'success', $user->id, "Đăng nhập bằng OTP thành công: {$user->username}");

                                return $this->respondSuccess($response);
                            } else {
                                $this->logAuthEvent('login', 'failed', $user->id, "Xác thực OTP thất bại (tài khoản chưa kích hoạt): {$user->username}");

                                return $this->respondWithError('Tài khoản đã ngưng hoạt động hoặc chưa được kích hoạt !', [], 501);
                            }
                        }
                    } else {
                        $check->count_wrong++;
                        $check->save();

                        $this->logAuthEvent('login', 'failed', $user->id, "Xác thực OTP thất bại (mã không chính xác): {$user->username}");

                        return $this->respondWithError('Mã OTP Không chính xác !', [], 500);
                    }
                } else {
                    $this->logAuthEvent('login', 'failed', $user->id, "Xác thực OTP thất bại (chưa có mã OTP): {$user->username}");

                    return $this->respondWithError('Mã OTP Không tồn tại !', [], 500);
                }
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    /**
     * Resend OTP
     *
     * Re-sends the OTP email for a user (blocked after 5 wrong attempts).
     *
     * @bodyParam user_id integer required The user ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": "Đã gửi lại thành công mã OTP",
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function resendOTP(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $checkRecord = OTP::where('user_id', $request->user_id)->first();
                if (! $checkRecord) {
                    return $this->respondWithError('Không tìm thấy dữ liệu !', [], 500);
                }
                if ($checkRecord->count_wrong >= 5) {
                    return $this->respondWithError('Đã quá số lần nhập sai mã sác thực không thể gửi lại mã !', [], 500);
                }
                $mailConfig = MailConfig::where('type', 'system')->first();
                $OTP = substr(rand(), 0, 6);
                $checkRecord->update([
                    'otp_code' => $OTP,
                    'expired' => now()->addMinutes(5),
                    'updated_at' => now(),
                    'count' => DB::raw('count + 1'),
                ]);
                SendMailVerifyOTP::dispatch(
                    User::find($checkRecord->user_id),
                    $OTP,
                    $mailConfig
                );

                return $this->respondSuccess('Đã gửi lại thành công mã OTP');
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    /**
     * Verify token
     *
     * Validates a one-time token (e.g. activation / reset) of a given type.
     *
     * @bodyParam token string required The token to verify. Example: 9f8c7b...
     * @bodyParam type string required The token type. Example: reset_password
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Xác thực thành công !",
     *   "data": {"id": 1, "user_id": 1, "token": "9f8c7b...", "type": "reset_password", "expired": "2026-06-12T00:05:00.000000Z"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function verifyToken(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $checkToken = Token::where('token', $request->token)->where('type', $request->type)->first();
                if (! $checkToken) {
                    return $this->respondWithError('Token không tồn tại !', [], 500);
                }

                if (now() > $checkToken->expired) {
                    return $this->respondWithError('Token đã hết hạn !', [], 500);
                }

                return $this->respondSuccess($checkToken, 'Xác thực thành công !');
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function createBusinessToCRM($data)
    {
        $data['currency_id'] = ($data['currency_id'] == 0) ? 129 : $data['currency_id']; // vietnam
        BusinessCRM::create($data);
    }

    public function getBusinessById($id)
    {
        return Business::find($id);
    }

    private function createEmployee($data)
    {
        $dataResult = [
            'full_name' => $data->full_name,
            'email' => $data->email,
            'phone' => $data->phone,
            'user_id' => $data->id,
            'status_work' => 1,
            'source_type' => 2,
            'code' => $data->business_id.$data->id.random_int(1111, 9999),
            'business_id' => $data->business_id,
            'is_default' => 1,
        ];

        return Employee::create($dataResult);
    }

    /**
     * Create default business data
     *
     * Bootstraps default CRM data (stock, default customer/supplier) for a business.
     *
     * @urlParam id integer required The business ID. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "name": "Hana English",
     *   "status": "is_active"
     * }
     */
    public function createBusinessDefault($id)
    {
        $businessPortal = $this->getBusinessById($id);
        $this->createBusinessToCRM($businessPortal->toArray());
        $this->createStockDefault([
            'business_id' => $id,
            'location_id' => null,
            'stock_name' => 'Kho '.$businessPortal->name,
            'stock_type' => 'type3',
            'is_active' => 1,
            'is_delete' => 0,
            'is_sync' => 0,
            'created_by' => null,
            'created_at' => now(),
            'is_default' => 1,
        ]);
        $this->createCustomerDefault($id);
        $this->createSupplierDefault($id);

        return $businessPortal;
    }

    /**
     * Register
     *
     * Registers either a `business` account (creates business + owner + defaults) or an
     * `individual` account, depending on the `type` field.
     *
     * @bodyParam type string required Either "business" or "individual". Example: individual
     * @bodyParam full_name string Required for individual. Example: John Doe
     * @bodyParam email string required The account email. Example: super@example.com
     * @bodyParam phone string The phone number. Example: 0900000000
     * @bodyParam password string Required for individual. Example: secret123
     * @bodyParam name string Required for business (business name). Example: Hana English
     * @bodyParam owner_name string Required for business. Example: Jane Owner
     * @bodyParam owner_email string Required for business. Example: owner@example.com
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Đăng ký tài khoản thành công",
     *   "data": {"id": 1, "full_name": "John Doe", "email": "super@example.com", "type": "individual", "is_active": 1},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function register(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                if ($request->type === 'business') {
                    $inputBusiness = [
                        'owner_name',
                        'owner_email',
                        'owner_job_title',
                        'owner_department',
                        'owner_phone',
                        'name',
                        'email',
                        'address',
                        'employee_size',
                        'payment_methods',
                    ];
                    $checkEmailExist = User::where('email', trim($request->owner_email))->first();
                    if ($checkEmailExist) {
                        return $this->respondWithError([
                            'field' => 'owner_email',
                            'message' => 'Email đã tồn tại !',
                        ], [], 500);
                    }
                    $dataBusiness = $request->only($inputBusiness);
                    $dataBusiness['status'] = 'no_activated';

                    $autoActive = env('AUTO_ACTIVE');
                    if ($autoActive == 1) {
                        $dataBusiness['is_active'] = 1;
                        $dataBusiness['status'] = 'is_active';
                        $dataBusiness['register_time'] = now()->format('Y-m-d');
                    }
                    // $urlCreateBusiness = env('PORTAL_URL') . '/api/portal/business/create';

                    $businessResult = Business::create($dataBusiness);
                    DB::commit();
                    $id = null;
                    if (! empty($businessResult)) {
                        $id = $businessResult->id;
                        $this->createBusinessDefault($id);
                        $this->createWorkflowDefault(env('OPERATION_URL').'/api/operation/workflow/create-object-workflow', ['business_id' => $id]);
                        $this->setWorkflowDefault(env('OPERATION_URL').'/api/operation/workflow/workflow-default', ['business_id' => $id]);
                    }

                    if ($autoActive == 1 && $id) {
                        $user = $this->createOwner($id, $request);
                        DB::commit();

                        if (! empty($user)) {
                            $this->createEmployee($user);
                        }
                        $this->addServicePackageTrialDefault($id);
                        $this->syncRolePermission->syncPermissionsBusiness($id, [], false);
                        $user->is_workflow_default = 1;
                        $user->save();
                        $user->is_verify = $user->verify_auth;

                        return $this->respondSuccess($user);
                    }

                    return $this->respondSuccess($businessResult);
                } else {
                    $inputIndividual = [
                        'full_name',
                        'avatar',
                        'email',
                        'phone',
                        'password',
                    ];
                    $checkEmailExist = User::where('email', trim($request->email))->first();
                    if ($checkEmailExist) {
                        return $this->respondWithError([
                            'field' => 'email',
                            'message' => 'Email đã tồn tại !',
                        ], [], 500);
                    }
                    $dataIndividual = $request->only($inputIndividual);
                    $dataIndividual['username'] = $request->email;
                    $dataIndividual['type'] = 'individual';
                    $dataIndividual['status'] = 'active';
                    $dataIndividual['is_active'] = 1;
                    $dataIndividual['verify_auth'] = env('VERIFY_AUTH');
                    $dataIndividual['password'] = bcrypt($request->password);
                    //
                    // business_id/role_id are NOT NULL; individual accounts are attached to the
                    // system business and its dedicated default role (seeded as INDIVIDUAL_USER
                    // in RoleSeeder) since they don't belong to a real business.
                    $systemBusinessId = Business::query()->min('id');
                    $dataIndividual['business_id'] = $systemBusinessId;
                    $role = Role::where('code', 'INDIVIDUAL_USER')->where('business_id', $systemBusinessId)->first();
                    if ($role) {
                        $dataIndividual['role_id'] = $role->id;
                    }
                    //
                    $dataIndividual['register_time'] = now()->format('Y-m-d');
                    $dataIndividual['trial_time'] = 7;
                    $expiration_time = now()->addDays(7);
                    $dataIndividual['expiration_time'] = $expiration_time->format('Y-m-d');
                    //
                    $refCount = Task::setAndGetReferenceCount('user');
                    $dataIndividual['code'] = Task::generateReferenceNumber('user', $refCount, 'USR');
                    //
                    $result = User::create($dataIndividual);

                    // // set permisson
                    // $getModuleForIndividual = Module::whereJsonContains('type', 'individual')->get();

                    // if ($result) {
                    //     if (count($getModuleForIndividual) > 0) {
                    //         foreach ($getModuleForIndividual as $key => $module) {
                    //             foreach ($module->type as $type) {
                    //                 if ($type == 'individual') {
                    //                     ModulePermission::create(['type' => $type, 'user_id' => $result->id, 'module_id' => $module->id, 'created_at' => now()]);
                    //                 }
                    //             }
                    //         }
                    //     }
                    // }
                    // //
                    // $urlAuth = env('CLIENT_URL').'/auth/login';
                    // $mailConfig = MailConfig::where('type', 'system')->first();
                    // //
                    // JobsMailRegister::dispatch(
                    //     $mailConfig,
                    //     $request->email,
                    //     $urlAuth
                    // );
                    if (! $result) {
                        throw new HttpException('Không thể đăng ký !');
                    }
                    $message = 'Đăng ký tài khoản thành công';

                    return $this->respondSuccess($result, $message);
                }
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    private function createWorkflowDefault($apiUrl = '', $data = [])
    {
        try {
            $jsonData = json_encode($data);

            $ch = curl_init($apiUrl);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: '.strlen($jsonData),
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {

                echo 'Lỗi cURL: '.curl_error($ch);
            }

            curl_close($ch);

            // Decode the JSON response into an associative array
            return json_decode($response, true);
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function setWorkflowDefault($url, $business_id)
    {
        return Http::withHeaders([
            'Accept' => '*/*',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/json',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-site',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'business-id' => $business_id,
            'device-code' => 'yfUW52kpfUKH',
        ])
            ->post($url, [
            ]);
    }

    private function createCustomerDefault($business_id)
    {
        $customer = Customer::where('is_default', 1)
            ->where('business_id', $business_id)
            ->where('type', 'customer')
            ->first();
        if (empty($customer)) {
            $code = 'kh_default_'.$business_id;
            $name = 'Khách hàng vãng lai';
            Customer::create([
                'code' => $code,
                'business_name' => $name,
                'business_id' => $business_id,
                'foreign_name' => $name,
                'type' => 'customer',
                'is_default' => 1,
            ]);
        }
    }

    private function createSupplierDefault($business_id)
    {
        $code = 'ncc_default_'.$business_id;
        $name = 'Nhà cung cấp mặc định';
        $customer = Customer::where('code', $code)
            ->first();
        if (empty($customer)) {
            Customer::create([
                'code' => $code,
                'business_name' => $name,
                'business_id' => $business_id,
                'foreign_name' => $name,
                'type' => 'supplier',
                'is_default' => 1,
            ]);
        }
    }

    public function createStockDefault(array $data)
    {
        StockCRM::create($data);
    }

    public function addServicePackageTrialDefault($business_id)
    {
        $getTrialDefault = Service::with(['packages'])->where('is_trial_default', 1)->first();
        if ($getTrialDefault) {
            if (! empty($getTrialDefault->packages)) {
                $arrInsert = [];
                $checkHaveOrNo = BusinessService::where('business_id', $business_id)
                    ->where('service_id', $getTrialDefault->id)
                    ->whereIn('package_id', array_column(collect($getTrialDefault->packages)->toArray(), 'id'))
                    ->first();
                if (! $checkHaveOrNo) {
                    foreach ($getTrialDefault->packages as $key => $value) {
                        $arrInsert[] = [
                            'business_id' => $business_id,
                            'service_id' => $getTrialDefault->id,
                            'package_id' => $value->id,
                            'date_active' => now()->format('Y-m-d'),
                            'date_expired' => now()->addMonths(1)->format('Y-m-d'),
                            'status' => 'is_active',
                            'service_type' => 'trial',
                            'time' => 1,
                            'created_at' => now(),
                        ];
                    }
                }

                if (! empty($arrInsert)) {
                    BusinessService::insert($arrInsert);
                }
            }
        }
    }

    public function createOwner($business_id, $request)
    {
        $role_id = Role::updateOrCreate([
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
        ])->id;

        return User::create([
            'full_name' => $request->owner_name,
            'username' => $request->owner_email,
            'email' => $request->owner_email,
            'phone' => $request->owner_phone,
            'password' => bcrypt($request->owner_phone),
            'is_active' => 1,
            'type' => 'owner',
            'role_id' => $role_id,
            'verify_auth' => env('VERIFY_AUTH') == null ? 1 : env('VERIFY_AUTH'),
            'business_id' => $business_id,
            'department' => $request->owner_department,
            'job_title' => $request->owner_job_title,
        ]);
    }

    public function createBusiness($apiUrl = '', $data = [])
    {
        try {
            $jsonData = json_encode($data);

            $ch = curl_init($apiUrl);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: '.strlen($jsonData),
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'Lỗi cURL: '.curl_error($ch);
            }

            curl_close($ch);

            // Decode the JSON response into an associative array
            return json_decode($response, true);
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    /**
     * Send account activation email
     *
     * Generates an activation token and emails an activation link to the user.
     *
     * @bodyParam user_id integer required The user ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Vui lòng kiểm tra email để kích hoạt tài khoản !",
     *   "data": {"id": 1, "username": "super", "email": "super@example.com"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function sendMailActiveAccount(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                if (! $request->user_id) {
                    throw new HttpException('Không tìm thấy người dùng');
                }
                $user = User::where('id', $request->user_id)->first();
                if (! $user) {
                    throw new HttpException('Không tìm thấy người dùng');
                }
                $mailConfig = MailConfig::where('type', 'system')->first();
                $token = $this->generateToken(64);
                Token::create([
                    'user_id' => $user->id,
                    'token' => $token,
                    'expired' => now()->addMinutes(5),
                    'type' => 'activation',
                    'created_at' => now(),
                ]);
                $urlActivation = env('CLIENT_URL').'/auth/account-activation?token='.$token;
                SendMailAccountActivation::dispatch($user, $urlActivation, $mailConfig);

                return $this->respondSuccess($user, 'Vui lòng kiểm tra email để kích hoạt tài khoản !');
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function generateRandomPassword($length = 8)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $password;
    }

    /**
     * Activate account
     *
     * Activates a user account using an activation token.
     *
     * @bodyParam token string required The activation token. Example: 9f8c7b...
     * @bodyParam type string required The token type. Example: activation
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Đã kích hoạt tài khoản thành công !",
     *   "data": {"id": 5, "user_id": 1, "type": "activation"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function accountActivation(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $token = $request->token;
                $type = $request->type;
                if (! isset($token)) {
                    return $this->respondWithError('Kích hoạt thất bại !', [], 500);
                }
                $check = Token::where('token', $token)->where('type', $type)->first();
                if (! $check) {
                    return $this->respondWithError('Token không tồn tại !', [], 500);
                }

                if (now() > $check->expired) {
                    return $this->respondWithError('Token đã hết hạn !', [], 500);
                }

                $user = User::where('id', $check->user_id)->first();
                if ($user) {
                    $user->is_active = 1;
                    $user->save();
                }
                $check->delete();

                return $this->respondSuccess($check, 'Đã kích hoạt tài khoản thành công !');
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    /**
     * Forgot password
     *
     * Sends a reset-password link (or activation link if the account is not yet active).
     *
     * @bodyParam email string required The account email / username. Example: super@example.com
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Vui lòng kiểm tra email để khôi phục mật khẩu !",
     *   "data": {"id": 1, "email": "super@example.com"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function forgotPassword(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $email = $request->email;
                $checkEmailExist = User::where('username', $email)->first();
                if (! $checkEmailExist) {
                    return $this->respondWithError('Email không tồn tại !', [], 500);
                } else {
                    $token = $this->generateToken(64);
                    $mailConfig = MailConfig::where('type', 'system')->first();
                    if ($checkEmailExist->is_active != 1) {
                        Token::updateOrCreate([
                            'user_id' => $checkEmailExist->id,
                            'type' => 'activation',
                        ], [
                            'user_id' => $checkEmailExist->id,
                            'type' => 'activation',
                            'token' => $token,
                            'expired' => now()->addMinutes(5),
                            'created_at' => now(),
                        ]);
                        $checkEmailExist->save();
                        $urlActivation = env('CLIENT_URL').'/auth/account-activation?token='.$token;
                        SendMailAccountActivation::dispatch($checkEmailExist, $urlActivation, $mailConfig);

                        return $this->respondWithError('Tài khoản chưa được kích hoạt vui lòng kiểm tra email để kích hoạt tài khoản !');
                    } else {
                        $urlResetPassword = env('CLIENT_URL').'/auth/reset-password?token='.$token;
                        Token::updateOrCreate([
                            'user_id' => $checkEmailExist->id,
                            'type' => 'reset_password',
                        ], [
                            'user_id' => $checkEmailExist->id,
                            'type' => 'reset_password',
                            'token' => $token,
                            'expired' => now()->addMinutes(5),
                            'created_at' => now(),
                        ]);
                        SendMailResetPassword::dispatch($checkEmailExist, $urlResetPassword, $mailConfig);

                        return $this->respondSuccess($checkEmailExist, 'Vui lòng kiểm tra email để khôi phục mật khẩu !');
                    }
                }
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    /**
     * Reset password
     *
     * Resets the password using a valid reset token.
     *
     * @bodyParam token string required The reset token. Example: 9f8c7b...
     * @bodyParam type string required The token type. Example: reset_password
     * @bodyParam password string required The new password. Example: newSecret123
     * @bodyParam confirm_password string required Must match password. Example: newSecret123
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Đặt lại mật khẩu thành công !",
     *   "data": {"id": 1, "username": "super", "email": "super@example.com"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function resetPassword(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $token = $request->token;
                $type = $request->type;
                if (! $token) {
                    return $this->respondWithError('Có lỗi xảy ra !', [], 500);
                }
                $check = Token::where('token', $token)->where('type', $type)->first();
                if (! $check) {
                    return $this->respondWithError('Token không tồn tại !', [], 500);
                }

                if (now() > $check->expired) {
                    return $this->respondWithError('Token đã hết hạn !', [], 500);
                }

                $user = User::where('id', $check->user_id)->first();
                if ($request->password != $request->confirm_password) {
                    return $this->respondWithError('Nhập lại mật khẩu không đúng !', [], 500);
                }

                $user->update(['password' => bcrypt($request->password)]);
                $check->delete();
                $mailConfig = MailConfig::where('type', 'system')->first();
                SendMailInformationAccount::dispatch($user, $request->password, $mailConfig);

                return $this->respondSuccess($user, 'Đặt lại mật khẩu thành công !');
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    /**
     * Check auth (SSO handoff)
     *
     * Validates an encrypted `access_id` payload and issues a fresh token for the user.
     *
     * @bodyParam salt string required Encryption salt (hex). Example: a1b2
     * @bodyParam iv string required Encryption IV (hex). Example: c3d4
     * @bodyParam da string required The encrypted payload (base64). Example: U2FsdGVk...
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "user": {"id": 1, "username": "super", "full_name": "John Doe"},
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGci..."
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function checkAuth(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataPayload = [
                'salt' => $request->salt,
                'iv' => $request->iv,
                'da' => $request->da,
            ];

            $input = (object) $this->CryptoJSAesDecrypt($dataPayload);

            if (empty($input->access_id)) {
                DB::rollBack();
                $this->logAuthEvent('login', 'failed', null, 'Đăng nhập SSO thất bại (thiếu access_id)');

                return $this->respondWithError('Bạn không có quyền truy câp access_id', [], 403);
            }

            $checkExit = AccessToken::where('id', $input->access_id)->first();

            if (empty($checkExit)) {
                DB::rollBack();
                $this->logAuthEvent('login', 'failed', null, 'Đăng nhập SSO thất bại (access_id không hợp lệ)');

                return $this->respondWithError('Bạn không có quyền truy câp', [], status: 403);
            }

            $user = User::where('id', $checkExit->user_id)->first();

            if ($user) {
                $dataUser = User::select([
                    'id',
                    'username',
                    'full_name',
                    'avatar',
                    'status',
                    'code',
                    'is_admin',
                    'type',
                ])
                    ->find($user->id);

                $token = $user->createToken('Tera Auth Private key');

                $response = [
                    'user' => $dataUser,
                    'token' => $token->accessToken,
                ];

                // $checkExit->delete();

                DB::commit();

                $this->logAuthEvent('login', 'success', $user->id, "Đăng nhập SSO thành công: {$user->username}");

                return $this->respondSuccess($response);
            } else {
                $message = 'Người dùng không tồn tại';
                DB::rollBack();
                $this->logAuthEvent('login', 'failed', $checkExit->user_id, "Đăng nhập SSO thất bại (người dùng không tồn tại): user_id={$checkExit->user_id}");

                return $this->respondWithError($message, [], 500);
            }
        } catch (Exception $err) {
            DB::rollBack();

            return $this->respondWithError($err->getMessage(), [], 403);
        }
    }

    /**
     * Logout
     *
     * Revokes the current access token and clears the user session.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": "Bạn đã đăng xuất thành công!",
     *   "code": 200,
     *   "errors": null
     * }
     * @response 401 {
     *   "success": false,
     *   "msg": "No permision!!",
     *   "data": null,
     *   "code": 401,
     *   "errors": []
     * }
     */
    public function logout(Request $request)
    {
        DB::beginTransaction();

        try {
            if (! Auth::guard('api')->user()) {
                return $this->respondWithError('No permision!!', [], 401);
            }

            $token = Auth::guard('api')->user()->token();

            $token->revoke();

            $user_id = Auth::guard('api')->user()->id;

            $session = Session::where('user_id', $user_id)->first();
            if ($session) {
                $session->delete();
            }

            DB::commit();

            $this->logAuthEvent('logout', 'success', $user_id, 'Đăng xuất thành công');

            $response = 'Bạn đã đăng xuất thành công!';

            return $this->respondSuccess($response);
        } catch (Exception $err) {
            return $this->respondWithError($err->getMessage(), [], 403);
        }
    }

    /**
     * Test (mail preview)
     *
     * Internal helper that renders the individual-registration mail template. Not part of the public API.
     *
     * @response 200 "<html>...rendered mail preview...</html>"
     */
    public function test()
    {
        return view('mail.RegisterIndividual');
    }

    /**
     * Reset password directly
     *
     * Resets a user's password by user id (admin / direct flow) and revokes existing tokens.
     *
     * @bodyParam user_id integer required The user ID. Example: 1
     * @bodyParam password string required The new password. Example: newSecret123
     * @bodyParam confirm_password string required Must match password. Example: newSecret123
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Đặt lại mật khẩu thành công !",
     *   "data": {"id": 1, "username": "super"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function resetDirectPassword(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $user = User::where('id', $request->user_id)->first();

                if (! $user) {
                    throw new HttpException('Không tìm thấy người dùng');
                }

                if ($request->password != $request->confirm_password) {
                    return $this->respondWithError('Nhập lại mật khẩu không đúng !', [], 500);
                }
                $userTokens = $user->tokens->pluck('id');
                $user->tokens()->whereIn('id', $userTokens)->delete();
                $user->update(['password' => bcrypt($request->password)]);

                return $this->respondSuccess($user, 'Đặt lại mật khẩu thành công !');
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    /**
     * Login with Google
     *
     * Verifies a Google ID token and signs the user in.
     *
     * @bodyParam client_id string required The Google OAuth client id. Example: 1234.apps.googleusercontent.com
     * @bodyParam access_token string required The Google ID token. Example: ya29...
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"user": {"id": 1, "email": "super@example.com"}, "token": "eyJ0eXAiOiJKV1Qi..."},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function loginGoogle(Request $request)
    {
        try {
            $CLIENT_ID = $_POST['client_id'];
            $id_token = $_POST['access_token'];
            $client = new Google_Client(['client_id' => $CLIENT_ID]);  // Specify the CLIENT_ID of the app that accesses the backend
            $tuan = $client->getAccessToken();
            $payload = $client->verifyIdToken($tuan);
            if ($payload) {
                $userid = $payload['sub'];
                // If request specified a G Suite domain:
                // $domain = $payload['hd'];
            } else {
                dd(0);
                // Invalid ID token
            }
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    /**
     * Turn off welcome screen
     *
     * Marks the authenticated user as no longer first-time (`is_first = 0`).
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": true,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function turnOffWelcome()
    {
        Auth::guard('api')->user()->update(['is_first' => 0]);

        return $this->respondSuccess(true);
    }
}
