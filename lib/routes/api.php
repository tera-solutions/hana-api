<?php

use App\Modules\System\Onboarding\Http\Controllers\OnboardingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['cors', 'json.response'])->group(function () {
    Route::group(['namespace' => 'Common'], function () {
        Route::get('/health', 'HealthCheckController@index');
        Route::get('/connect', 'HealthCheckController@checkConnect');
    });
});

Route::group(['middleware' => ['cors', 'json.response'], 'prefix' => 'auth'], function () {
    Route::group(['namespace' => 'Common'], function () {
        Route::post('/device/init', 'CommonController@initClientCode');
        Route::get('/device/days-in-month', 'CommonController@getDayInMonth');
    });
    Route::group(['namespace' => 'Auth'], function () {
        Route::post('/login', 'ApiAuthController@login');
        Route::post('/refresh-token', 'ApiAuthController@refreshToken');
        Route::post('/verify-otp', 'ApiAuthController@verifyOTP');
        Route::post('/check-auth', 'ApiAuthController@checkAuth');
        Route::post('/forgot-password', 'ApiAuthController@forgotPassword');
        Route::post('/register', 'ApiAuthController@register');
        Route::post('/logout', 'ApiAuthController@logout');
        Route::post('/reset-password', 'ApiAuthController@resetPassword');
        Route::post('/account-activation', 'ApiAuthController@accountActivation');
        Route::post('/resend-otp', 'ApiAuthController@resendOTP');
        Route::post('/verify-token', 'ApiAuthController@verifyToken');
        Route::get('/test', 'ApiAuthController@test');
        Route::post('/send-mail-active-account', 'ApiAuthController@sendMailActiveAccount');
        Route::post('/reset-direct-password', 'ApiAuthController@resetDirectPassword');
        Route::post('/login-google', 'ApiAuthController@loginGoogle');
        Route::post('/turn-off-welcome', 'ApiAuthController@turnOffWelcome');
        Route::post('/business-default/{id}', 'ApiAuthController@createBusinessDefault');
    });

    // Public self-service center registration (Teacher app). Clean modular
    // handler — kept separate from the legacy 'register' above, which is wired
    // to external portal/CRM microservices.
    Route::post('/register-school', [OnboardingController::class, 'register']);

    Route::group(['namespace' => 'User'], function () {
        Route::get('/profile', 'UserController@getProfile');
        Route::put('/profile', 'UserController@updateProfile');
        Route::post('/profile/change-password', 'UserController@changePassword');
    });

    // Frontend bootstrap: all model enumerations (status/type/gender/…). Auth-guarded.
    Route::group(['middleware' => 'auth.tera', 'namespace' => 'Common'], function () {
        Route::get('/metadata', 'MetadataController@index');
    });

});

Route::group(['middleware' => ['cors', 'json.response'], 'prefix' => 'file', 'namespace' => 'File'], function () {
    Route::post('/upload', 'AjaxController@upload');
    Route::get('/download/{id}', 'AjaxController@download');
    Route::post('/import', 'AjaxController@importFile');
    Route::post('/ckeditor-upload', 'AjaxController@ckeditorUpload')->name('file.ckeditorUpload');
});
