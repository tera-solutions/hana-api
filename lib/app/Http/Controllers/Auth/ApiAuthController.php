<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\SyncPermissionRole;
use App\Http\Controllers\Controller;
use App\Jobs\MailRegister as JobsMailRegister;
use App\Jobs\SendMailAccountActivation;
use App\Jobs\SendMailInformationAccount;
use App\Jobs\SendMailResetPassword;
use App\Jobs\SendMailVerifyOTP;
use App\Models\BusinessPortal;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Session;
use App\Models\User;
use App\Models\AccessToken;
use App\Models\BusinessCRM;
use App\Models\BusinessService;
use App\Models\MailConfig;
use App\Models\Module;
use App\Models\OTP;
use App\Models\Role;
use App\Models\Token;
use App\Models\ModulePermission;
use App\Models\Service;
use App\Models\StockCRM;
use Carbon\Carbon;
use Exception;
use Google_Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Package\Exception\HttpException;

class ApiAuthController extends Controller
{
    public $syncRolePermission;

    public function __construct(SyncPermissionRole $syncRolePermissionHelper)
    {
        $this->syncRolePermission = $syncRolePermissionHelper;
    }

    public function login(Request $request)
    {
        DB::beginTransaction();
        try {
            $input = $request;

            $validator = Validator::make($request->all(), []);
            $device_code = $request->header("device-code");

            if ($validator->fails()) {
                $message = "Vui lòng nhập đủ thông tin";
                $errors = $validator->errors()->all();
                DB::rollBack();
                return $this->respondWithError($message, $errors, 422);
            }

            if (!$device_code) {
                $message = "Không tìm thấy thiết bị";
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
                        "id",
                        "username",
                        "full_name",
                        "email",
                        "avatar",
                        'status',
                        'code',
                        'is_admin',
                        'reps_login'
                    ])
                        ->find($user->id);

                    if ($user->type === 'member') {
                        $businessStatus = BusinessPortal::where('id', $user->business_id)->pluck('status')->first();
                        if ($businessStatus === 'cancelled') {
                            throw new HttpException("Thành viên không thể đăng nhập vào doanh nghiệp bị hủy kích hoạt !");
                        }
                    }
                    if ($user->time_block) {
                        if (now() <= $user->time_block) {
                            return $this->respondWithError("Tài khoản đã bị khóa, hãy đăng nhập sau " . Carbon::parse($user->time_block)->format('d/m/Y H:i'), [], 501);
                        } else {
                            $user->time_block = null;
                            $user->save();
                            DB::commit();
                        }
                    }

                    if ($user->is_active != 1) {
                        return $this->respondWithError("Tài khoản đã ngưng hoạt động hoặc chưa được kích hoạt !", [], 501);
                    }
                    $data = [];
                    $openModeVerify = env('VERIFY_AUTH');
                    if (!$openModeVerify) {
                        $user->verify_auth = 0;
                    }

                    $response = $this->createToken($user);
                    DB::commit();
                    return $this->respondSuccess($response, "Đăng nhập thành công !");
                    // if ($user->verify_auth == 1) {
                    //     $data = $this->sendOTP($data, $dataUser, $user);
                    //     DB::commit();
                    //     return $this->respondSuccess($data, "Mã OTP đã được gửi đến địa chỉ " . $dataUser->email ?? null);
                    // } else {
                    //     $response = $this->createToken($user);
                    //     DB::commit();
                    //     return $this->respondSuccess($response, "Đăng nhập thành công !");
                    // }
                } else {
                    $message = "Mật khẩu không hợp lệ";
                    DB::rollBack();
                    return $this->respondWithError([
                        "field" => "password",
                        "message" => $message
                    ], [], 500);
                }
            } else {
                $message = "Tài khoản không tồn tại";
                DB::rollBack();
                return $this->respondWithError([
                    "field" => "username",
                    "message" => $message
                ], [], 500);
            }
        } catch (\Exception $err) {
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
            "user_id" => $dataUser->id
        ], [
            "user_id" => $dataUser->id,
            "otp_code" => $OTP,
            "expired" => now()->addMinutes(5),
            "created_at" => now(),
            "count" => DB::raw('count + 1'),
            "count_wrong" => 0
        ]);
        SendMailVerifyOTP::dispatch($dataUser, $OTP, $mailConfig);
        $data['verify_auth'] = $user->verify_auth;
        $data['user']['id'] = $dataUser->id;
        return $data;
    }

    private function createToken($user)
    {
        $token = $user->createToken('Tera Auth Private key');
        $user->reps_login++;
        $user->save();
        return [
            'verify_auth' => $user->verify_auth,
            'user' => $user,
            'token' => $token->accessToken,
            'access_id' => $token->accessTokenId ?? null
        ];
    }

    public function generateToken($length = 32)
    {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }

    public function verifyOTP(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $user = User::where('id', $request->user_id)->first();
                if (!$user) {
                    return $this->respondWithError("Tài khoản không tồn tại !", [], 500);
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
                        return $this->respondWithError("Đã quá số lần nhập sai mã xác thực, tài khoản bị khóa 1 giờ!", [], 501);
                    }
                    if ($check->otp_code == $request->otp_code) {
                        if (now() > $check->expired) {
                            return $this->respondWithError("Mã OTP đã hết hạn !", [], 501);
                        } else {
                            if ($user->is_active == 1) {
                                $token = $user->createToken('Tera Auth Private key');
                                $user->reps_login++;
                                $user->save();
                                $response = [
                                    'verify_auth' => $user->verify_auth,
                                    'user' => $user,
                                    'token' => $token->accessToken,
                                    'access_id' => isset($token->token->id) ? $token->token->id : null
                                ];
                                $check->delete();
                                return $this->respondSuccess($response);
                            } else {
                                return $this->respondWithError("Tài khoản đã ngưng hoạt động hoặc chưa được kích hoạt !", [], 501);
                            }
                        }
                    } else {
                        $check->count_wrong++;
                        $check->save();
                        return $this->respondWithError("Mã OTP Không chính xác !", [], 500);
                    }
                } else {
                    return $this->respondWithError("Mã OTP Không tồn tại !", [], 500);
                }
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function resendOTP(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $checkRecord = OTP::where('user_id', $request->user_id)->first();
                if (!$checkRecord) {
                    return $this->respondWithError("Không tìm thấy dữ liệu !", [], 500);
                }
                if ($checkRecord->count_wrong >= 5) {
                    return $this->respondWithError("Đã quá số lần nhập sai mã sác thực không thể gửi lại mã !", [], 500);
                }
                $mailConfig = MailConfig::where('type', 'system')->first();
                $OTP = substr(rand(), 0, 6);
                $checkRecord->update([
                    "otp_code" => $OTP,
                    "expired" => now()->addMinutes(5),
                    "updated_at" => now(),
                    "count" => DB::raw('count + 1')
                ]);
                SendMailVerifyOTP::dispatch(
                    User::find($checkRecord->user_id),
                    $OTP,
                    $mailConfig
                );
                return $this->respondSuccess("Đã gửi lại thành công mã OTP");
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function verifyToken(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $checkToken = Token::where('token', $request->token)->where('type', $request->type)->first();
                if (!$checkToken) {
                    return $this->respondWithError("Token không tồn tại !", [], 500);
                }

                if (now() > $checkToken->expired) {
                    return $this->respondWithError("Token đã hết hạn !", [], 500);
                }

                return $this->respondSuccess($checkToken, "Xác thực thành công !");
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
        return BusinessPortal::find($id);
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
            'code' => $data->business_id . $data->id . random_int(1111, 9999),
            'business_id' => $data->business_id,
            'is_default' => 1
        ];
        return Employee::create($dataResult);
    }

    public function createBusinessDefault($id)
    {
        $businessPortal = $this->getBusinessById($id);
        $this->createBusinessToCRM($businessPortal->toArray());
        $this->createStockDefault([
            'business_id' => $id,
            'location_id' => null,
            'stock_name' => 'Kho ' . $businessPortal->name,
            'stock_type' => 'type3',
            'is_active' => 1,
            'is_delete' => 0,
            'is_sync' => 0,
            'created_by' => null,
            'created_at' => now(),
            'is_default' => 1
        ]);
        $this->createCustomerDefault($id);
        $this->createSupplierDefault($id);

        return $businessPortal;
    }

    public function register(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                if ($request->type === 'business') {
                    $inputBusiness = [
                        "owner_name",
                        "owner_email",
                        "owner_job_title",
                        "owner_department",
                        "owner_phone",
                        "name",
                        "email",
                        "address",
                        "employee_size",
                        "payment_methods"
                    ];
                    $checkEmailExist = User::where('email', trim($request->owner_email))->first();
                    if ($checkEmailExist) {
                        return $this->respondWithError([
                            "field" => "owner_email",
                            "message" => "Email đã tồn tại !"
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

                    $businessResult = BusinessPortal::create($dataBusiness);
                    DB::commit();
                    $id = null;
                    if (!empty($businessResult)) {
                        $id = $businessResult->id;
                        $this->createBusinessDefault($id);
                        $this->createWorkflowDefault(env('OPERATION_URL') . "/api/operation/workflow/create-object-workflow", ["business_id" => $id]);
                        $this->setWorkflowDefault(env('OPERATION_URL') . "/api/operation/workflow/workflow-default", ["business_id" => $id]);
                    }

                    if ($autoActive == 1 && $id) {
                        $user = $this->createOwner($id, $request);
                        DB::commit();

                        if (!empty($user)) {
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
                        "full_name",
                        "email",
                        "phone",
                        "password"
                    ];
                    $checkEmailExist = User::where('email', trim($request->email))->first();
                    if ($checkEmailExist) {
                        return $this->respondWithError([
                            "field" => "email",
                            "message" => "Email đã tồn tại !"
                        ], [], 500);
                    }
                    $dataIndividual = $request->only($inputIndividual);
                    $dataIndividual['username'] = $request->email;
                    $dataIndividual['type'] = "individual";
                    $dataIndividual['status_account'] = 'is_active';
                    $dataIndividual['is_active'] = 1;
                    $dataIndividual['verify_auth'] = env('VERIFY_AUTH');
                    $dataIndividual['password'] = bcrypt($request->password);
                    //
                    $role = Role::where('is_default', 1)->whereNull('business_id')->where('type', 'user')->first();
                    if ($role) {
                        $dataIndividual['role_id'] = $role->id;
                    }
                    //
                    $dataIndividual['register_time'] = now()->format('Y-m-d');
                    $dataIndividual['trial_time'] = 7;
                    $expiration_time = now()->addDays(7);
                    $dataIndividual['expiration_time'] = $expiration_time->format('Y-m-d');
                    //
                    $result = User::create($dataIndividual);

                    // set permisson
                    $getModuleForIndividual = Module::whereJsonContains('type', 'individual')->get();

                    if ($result) {
                        if (count($getModuleForIndividual) > 0) {
                            foreach ($getModuleForIndividual as $key => $module) {
                                foreach ($module->type as $type) {
                                    if ($type == "individual") {
                                        ModulePermission::create(["type" => $type, 'user_id' => $result->id, 'module_id' => $module->id, 'created_at' => now()]);
                                    }
                                }
                            }
                        }
                    }
                    //
                    $urlAuth = env('CLIENT_URL') . '/auth/login';
                    $mailConfig = MailConfig::where('type', 'system')->first();
                    //
                    JobsMailRegister::dispatch(
                        $mailConfig,
                        $request->email,
                        $urlAuth
                    );
                    if (!$result) {
                        throw new HttpException("Không thể đăng ký !");
                    }
                    $message = "Đăng ký tài khoản thành công";
                    return $this->respondSuccess($result, $message);
                }
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    private function createWorkflowDefault($apiUrl = "", $data = [])
    {
        try {
            $jsonData = json_encode($data);

            $ch = curl_init($apiUrl);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ));

            $response = curl_exec($ch);

            if (curl_errno($ch)) {

                echo 'Lỗi cURL: ' . curl_error($ch);
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
            $code = 'kh_default_' . $business_id;
            $name = 'Khách hàng vãng lai';
            Customer::create([
                'code' => $code,
                'business_name' => $name,
                'business_id' => $business_id,
                'foreign_name' => $name,
                'type' => 'customer',
                'is_default' => 1
            ]);
        }
    }

    private function createSupplierDefault($business_id)
    {
        $code = 'ncc_default_' . $business_id;
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
                'is_default' => 1
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
            'job_title' => $request->owner_job_title
        ]);
    }

    public function createBusiness($apiUrl = "", $data = [])
    {
        try {
            $jsonData = json_encode($data);

            $ch = curl_init($apiUrl);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ));

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'Lỗi cURL: ' . curl_error($ch);
            }

            curl_close($ch);
            // Decode the JSON response into an associative array
            return json_decode($response, true);
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function sendMailActiveAccount(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                if (!$request->user_id) {
                    throw new HttpException("Không tìm thấy người dùng");
                }
                $user = User::where('id', $request->user_id)->first();
                if (!$user) {
                    throw new HttpException("Không tìm thấy người dùng");
                }
                $mailConfig = MailConfig::where('type', 'system')->first();
                $token = $this->generateToken(64);
                Token::create([
                    'user_id' => $user->id,
                    'token' => $token,
                    'expired' => now()->addMinutes(5),
                    'type' => 'activation',
                    'created_at' => now()
                ]);
                $urlActivation = env('CLIENT_URL') . '/auth/account-activation?token=' . $token;
                SendMailAccountActivation::dispatch($user, $urlActivation, $mailConfig);
                return $this->respondSuccess($user, "Vui lòng kiểm tra email để kích hoạt tài khoản !");
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

    public function accountActivation(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $token = $request->token;
                $type = $request->type;
                if (!isset($token)) {
                    return $this->respondWithError("Kích hoạt thất bại !", [], 500);
                }
                $check = Token::where('token', $token)->where('type', $type)->first();
                if (!$check) {
                    return $this->respondWithError("Token không tồn tại !", [], 500);
                }

                if (now() > $check->expired) {
                    return $this->respondWithError("Token đã hết hạn !", [], 500);
                }

                $user = User::where('id', $check->user_id)->first();
                if ($user) {
                    $user->is_active = 1;
                    $user->save();
                }
                $check->delete();
                return $this->respondSuccess($check, "Đã kích hoạt tài khoản thành công !");
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $email = $request->email;
                $checkEmailExist = User::where('username', $email)->first();
                if (!$checkEmailExist) {
                    return $this->respondWithError("Email không tồn tại !", [], 500);
                } else {
                    $token = $this->generateToken(64);
                    $mailConfig = MailConfig::where('type', 'system')->first();
                    if ($checkEmailExist->is_active != 1) {
                        Token::updateOrCreate([
                            'user_id' => $checkEmailExist->id,
                            'type' => 'activation'
                        ], [
                            'user_id' => $checkEmailExist->id,
                            'type' => 'activation',
                            'token' => $token,
                            'expired' => now()->addMinutes(5),
                            'created_at' => now()
                        ]);
                        $checkEmailExist->save();
                        $urlActivation = env('CLIENT_URL') . '/auth/account-activation?token=' . $token;
                        SendMailAccountActivation::dispatch($checkEmailExist, $urlActivation, $mailConfig);
                        return $this->respondWithError("Tài khoản chưa được kích hoạt vui lòng kiểm tra email để kích hoạt tài khoản !");
                    } else {
                        $urlResetPassword = env('CLIENT_URL') . '/auth/reset-password?token=' . $token;
                        Token::updateOrCreate([
                            'user_id' => $checkEmailExist->id,
                            'type' => 'reset_password'
                        ], [
                            'user_id' => $checkEmailExist->id,
                            'type' => 'reset_password',
                            'token' => $token,
                            'expired' => now()->addMinutes(5),
                            'created_at' => now()
                        ]);
                        SendMailResetPassword::dispatch($checkEmailExist, $urlResetPassword, $mailConfig);
                        return $this->respondSuccess($checkEmailExist, "Vui lòng kiểm tra email để khôi phục mật khẩu !");
                    }
                }
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $token = $request->token;
                $type = $request->type;
                if (!$token) {
                    return $this->respondWithError("Có lỗi xảy ra !", [], 500);
                }
                $check = Token::where('token', $token)->where('type', $type)->first();
                if (!$check) {
                    return $this->respondWithError("Token không tồn tại !", [], 500);
                }

                if (now() > $check->expired) {
                    return $this->respondWithError("Token đã hết hạn !", [], 500);
                }

                $user = User::where('id', $check->user_id)->first();
                if ($request->password != $request->confirm_password) {
                    return $this->respondWithError("Nhập lại mật khẩu không đúng !", [], 500);
                }

                $user->update(['password' => bcrypt($request->password)]);
                $check->delete();
                $mailConfig = MailConfig::where('type', 'system')->first();
                SendMailInformationAccount::dispatch($user, $request->password, $mailConfig);
                return $this->respondSuccess($user, "Đặt lại mật khẩu thành công !");
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

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
                return $this->respondWithError("Bạn không có quyền truy câp access_id", [], 403);
            }

            $checkExit = AccessToken::where("id", $input->access_id)->first();

            if (empty($checkExit)) {
                return $this->respondWithError("Bạn không có quyền truy câp", [], status: 403);
            }

            $user = User::where("id", $checkExit->user_id)->first();

            if ($user) {
                $dataUser = User::select([
                    "id",
                    "username",
                    "full_name",
                    "avatar",
                    'status',
                    'code',
                    'is_admin',
                    'type'
                ])
                    ->find($user->id);

                $token = $user->createToken('Tera Auth Private key');

                $response = [
                    'user' => $dataUser,
                    'token' => $token->accessToken
                ];

                // $checkExit->delete();

                DB::commit();
                return $this->respondSuccess($response);
            } else {
                $message = "Người dùng không tồn tại";
                DB::rollBack();
                return $this->respondWithError($message, [], 500);
            }
        } catch (\Exception $err) {
            DB::rollBack();
            return $this->respondWithError($err->getMessage(), [], 403);
        }
    }

    public function logout(Request $request)
    {
        DB::beginTransaction();

        try {
            if (!Auth::guard('api')->user()) {
                return $this->respondWithError("No permision!!", [], 401);
            }

            $token = Auth::guard('api')->user()->token();

            $token->revoke();

            $user_id = Auth::guard('api')->user()->id;

            $session = Session::where("user_id", $user_id)->first();
            if ($session) {
                $session->delete();
            }

            DB::commit();

            $response = "Bạn đã đăng xuất thành công!";

            return $this->respondSuccess($response);
        } catch (\Exception $err) {
            return $this->respondWithError($err->getMessage(), [], 403);
        }
    }

    public function test()
    {
        return view('mail.RegisterIndividual');
    }

    public function resetDirectPassword(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $user = User::where('id', $request->user_id)->first();

                if (!$user) {
                    throw new HttpException("Không tìm thấy người dùng");
                }

                if ($request->password != $request->confirm_password) {
                    return $this->respondWithError("Nhập lại mật khẩu không đúng !", [], 500);
                }
                $userTokens = $user->tokens->pluck('id');
                $user->tokens()->whereIn('id', $userTokens)->delete();
                $user->update(['password' => bcrypt($request->password)]);
                return $this->respondSuccess($user, "Đặt lại mật khẩu thành công !");
            });
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

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
                //$domain = $payload['hd'];
            } else {
                dd(0);
                // Invalid ID token
            }
        } catch (HttpException $e) {
            throw new HttpException($e->getMessage());
        }
    }

    public function turnOffWelcome()
    {
        Auth::guard('api')->user()->update(['is_first' => 0]);
        return $this->respondSuccess(true);
    }
}
