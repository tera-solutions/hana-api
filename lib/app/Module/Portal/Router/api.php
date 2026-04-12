<?php

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/11/2020
 * Time: 9:09 PM
 */

namespace App\Module\Portal\Router;

// admin
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['cors', 'json.response'], 'prefix' => 'api'], function () {
    Route::group(['middleware' => ['auth.tera']], function () {
        // User
        Route::group([
            'namespace' => 'App\Module\Portal\Controllers\User',
            'prefix' => 'user',
        ], function () {
            Route::get('/get-profile', 'ApiController@getProfile');
            Route::put('/change-password', 'ApiController@changePassword');
            Route::put('/update-profile', 'ApiController@updateProfile');
            Route::put('/update-avatar', 'ApiController@updateAvatar');
            Route::put('/change-setting', 'ApiController@changeSetting');
            Route::put('/change-language', 'ApiController@changeLanguage');
        });
        //
        // Mail
        Route::group([
            'namespace' => 'App\Module\Portal\Controllers\Mail',
            'prefix' => 'mail',
        ], function () {
            Route::get('/list', 'ApiController@list');
            Route::get('/detail/{id}', 'ApiController@detail');
            Route::post('/send-mail', 'ApiController@sendMail');
        });

        // Activity Log
        Route::group([
            'namespace' => 'App\Module\Portal\Controllers\ActivityLog',
            'prefix' => 'activity-log',
        ], function () {
            Route::get('/list', 'ApiController@list');
        });


        // Comments
        Route::group([
            'namespace' => 'App\Module\Portal\Controllers\Comment',
            'prefix' => 'comment',
        ], function () {
            Route::get('/list', 'ApiController@list');
            Route::get('/detail/{id}', 'ApiController@detail');
            Route::post('/create', 'ApiController@create');
            Route::put('/update/{id}', 'ApiController@update');
            Route::delete('/delete/{id}', 'ApiController@delete');
        });

        // Notification
        Route::group([
            'namespace' => 'App\Module\Portal\Controllers\Notification',
            'prefix' => 'notification',
        ], function () {
            Route::get('/list', 'ApiController@list');
            Route::get('/read-notification/{id}', 'ApiController@readNotification');
            Route::get('/detail/{id}', 'ApiController@detail');
            Route::post('/create', 'ApiController@create');
            Route::put('/update/{id}', 'ApiController@update');
            Route::delete('/delete/{id}', 'ApiController@delete');
        });

        // Attachment
        Route::group([
            'namespace' => 'App\Module\Portal\Controllers\Attachment',
            'prefix' => 'attachment',
        ], function () {
            Route::get('/list', 'ApiController@list');
            Route::get('/detail/{id}', 'ApiController@detail');
            Route::post('/create', 'ApiController@create');
            Route::put('/update/{id}', 'ApiController@update');
            Route::delete('/delete/{id}', 'ApiController@delete');
            Route::get('/download/{id}', 'ApiController@download');
        });


        // chat
        Route::group(['prefix' => 'util'], function () {
            Route::group(['namespace' => 'App\Module\Portal\Controllers\Chat', 'prefix' => 'friend'], function () {
                Route::get('/list', 'GroupUserController@listFriend');
                Route::get('/detail', 'GroupUserController@getDetailFriend');
            });

            Route::group(['namespace' => 'App\Module\Portal\Controllers\Chat', 'prefix' => 'chat'], function () {
                Route::get('/list', 'ChatController@list');
                Route::get('/detail/{id}', 'ChatController@detail');
                Route::post('/send-message', 'ChatController@sendMessage');
                Route::post('/send-image', 'ChatController@sendImage');
                Route::post('/read-message', 'ChatController@readMessage');
                Route::put('/update/{id}', 'ChatController@update');
                Route::delete('/delete/{id}', 'ChatController@delete');
            });

            Route::group(['namespace' => 'App\Module\Portal\Controllers\Chat', 'prefix' => 'group'], function () {
                Route::get('/list', 'GroupController@list');
                Route::get('/list-member', 'GroupController@listMember');
                Route::get('/detail/{id}', 'GroupController@detail');
                Route::post('/create', 'GroupController@create');
                Route::put('/update/{id}', 'GroupController@update');
                Route::delete('/delete/{id}', 'GroupController@delete');
                Route::post('/delete-member', 'GroupController@deleteMember');
                Route::post('/add-member', 'GroupController@addMember');
                Route::post('/block-member', 'GroupController@blockMember');
            });

            Route::group(['namespace' => 'App\Module\Portal\Controllers\Chat', 'prefix' => 'group-user'], function () {
                Route::get('/list', 'GroupUserController@list');
                Route::get('/detail', 'GroupUserController@detail');
                Route::delete('/delete/{id}', 'GroupUserController@delete');
            });
        });

        // wallet
        Route::group([
            'namespace' => 'App\Module\Portal\Controllers\Wallet',
            'prefix' => 'wallet'
        ], function () {
            Route::post('/recharge', 'ApiController@recharge');
            Route::post('/withdrawal', 'ApiController@withdrawal');
            Route::post('/verify-otp', 'ApiController@verifyOTP');
            Route::get('/get-qr', 'ApiController@getQR');
            Route::get('/get-amount', 'ApiController@getAmount');
            Route::post('/create-transaction-session', 'ApiController@createTransactionSession');
            Route::post('/resend-otp', 'ApiController@resendOTP');
        });

        //  role
        Route::group([
            'namespace' => 'App\Module\Portal\Controllers\Role',
            'prefix' => 'role'
        ], function () {
            Route::get('/list', 'ApiController@list');
            Route::get('/list-module', 'ApiController@listModule');
            Route::get('/detail/{id}', 'ApiController@detail');
            Route::post('/create', 'ApiController@create');
            Route::put('/update/{id}', 'ApiController@update');
            Route::delete('/delete/{id}', 'ApiController@delete');
            Route::get('/list-config-control', 'ApiController@listConfigControl');
            Route::get('/role-has-permission', 'ApiController@roleHasPermission');
            Route::post('/config-permission', 'ApiController@configPermission');
            Route::get('/role-has-permission-detail', 'ApiController@roleHasPermissionDetail');
            Route::get('/config-permission-default', 'ApiController@getPermissionDefault');
        });


        // cart
        Route::group([
            'namespace' => 'App\Module\Portal\Controllers\Cart',
            'prefix' => 'cart'
        ], function () {
            Route::get('/list', 'ApiController@list');
            Route::post('/create', 'ApiController@create');
            Route::put('/update/{id}', 'ApiController@update');
            Route::delete('/delete/{id}', 'ApiController@delete');
            Route::put('/replace/{id}', 'ApiController@replace');
            Route::post('/payment-cart', 'ApiController@getPaymentCart');
            Route::get('/count-cart', 'ApiController@getCountCart');
        });

        // bill
        Route::group([
            'namespace' => 'App\Module\Portal\Controllers\Bill',
            'prefix' => 'bill'
        ], function () {
            Route::get('/list', 'ApiController@list');
            Route::get('/detail/{id}', 'ApiController@detail');
            Route::post('/create', 'ApiController@create');
            Route::delete('/delete/{id}', 'ApiController@delete');
            Route::put('/pay', 'ApiController@pay');
            Route::put('/transfer', 'ApiController@transfer');
        });
    });
    Route::group([
        'namespace' => 'App\Module\Portal\Controllers\SyncLocalData',
        'prefix' => 'sync',
    ], function () {
        Route::get('/pull-change', 'SyncLocalDataController@pullChanges');
        Route::post('/push-change', 'SyncLocalDataController@pushChanges');
    });

    // Business
    Route::group([
        'namespace' => 'App\Module\Portal\Controllers\Business',
        'prefix' => 'business',
    ], function () {
        Route::get('/list', 'ApiController@list');
        Route::get('/detail/{id}', 'ApiController@detail');
        Route::post('/create', 'ApiController@create');
        Route::put('/update/{id}', 'ApiController@update');
        Route::delete('/delete/{id}', 'ApiController@delete');
        Route::post('/register', 'ApiController@register');
        Route::put('/save', 'ApiController@save');
        Route::get('/get-info', 'ApiController@getInfo');
    });

    // Member
    Route::group([
        'namespace' => 'App\Module\Portal\Controllers\Member',
        'prefix' => 'member',
    ], function () {
        Route::get('/list', 'ApiController@list');
        Route::get('/detail/{id}', 'ApiController@detail');
        Route::post('/create', 'ApiController@create');
        Route::put('/update/{id}', 'ApiController@update');
        Route::delete('/delete/{id}', 'ApiController@delete');
        Route::post('/change-password', 'ApiController@changePassword');
        Route::post('/permission', 'ApiController@permission');
        Route::post('/add-member-to-module', 'ApiController@addToModule');
        Route::post('/remove-member-in-module', 'ApiController@removeMemberModule');
        Route::put('/update-status', 'ApiController@updateStatus');
        Route::put('/config-role', 'ApiController@configRole');
    });

    // Module
    Route::group([
        'namespace' => 'App\Module\Portal\Controllers\Module',
        'prefix' => 'module',
    ], function () {
        Route::get('/list', 'ApiController@list');
        Route::post('/create', 'ApiController@create');
        Route::get('/detail/{id}', 'ApiController@detail');
        Route::put('/update/{id}', 'ApiController@update');
        Route::delete('/delete/{id}', 'ApiController@delete');
    });

    // store
    Route::group([
        'namespace' => 'App\Module\Portal\Controllers\BusinessLocation',
        'prefix' => 'business-location'
    ], function () {
        Route::get('/list', 'BussinessLocationController@list');
        Route::get('/detail/{id}', 'BussinessLocationController@detail');
        Route::post('/push-changes', 'BussinessLocationController@pushChanges');
        Route::post('/create', 'BussinessLocationController@create');
        Route::put('/update/{id}', 'BussinessLocationController@update');
        Route::delete('/delete/{id}', 'BussinessLocationController@delete');
    });

    // Service
    Route::group([
        'namespace' => 'App\Module\Portal\Controllers\Service',
        'prefix' => 'service'
    ], function () {
        Route::get('/list', 'ApiController@list');
        Route::get('/list-availability', 'ApiController@listAvailability');
        Route::get('/list-package', 'ApiController@listPackage');
        Route::post('/calculate-old-package', 'ApiController@calculateOldPackage');
        Route::get('/detail/{id}', 'ApiController@detail');
    });
});
