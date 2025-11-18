<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\api\PermissionController
use App\Http\Controllers\Api\Permissions\PermissionController;
;

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

Route::namespace("Api")->group(function () {
    Route::controller('AppController')->group(function () {
        Route::any('general-setting', 'generalSetting');
        Route::get('get-countries', 'getCountries');
        Route::get('language/{key}', 'getLanguage');
        Route::get('policies', 'policies');
        Route::get('faq', 'faq');
        Route::get('zones', 'zone');
    });



        Route::controller(PermissionController::class)->group(function () {
        // Route::any('general-setting', 'generalSetting');
      
        Route::post('create-permission', 'CreatePermissons');
        Route::get('create-permission', 'CreatePermissons');
        // Route::get('language/{key}', 'getLanguage');
        // Route::get('policies', 'policies');
        // Route::get('faq', 'faq');
        // Route::get('zones', 'zone');
    });
});

Route::namespace('Api\User')->group(function () {
    Route::namespace('Auth')->group(function () {
        Route::controller('LoginController')->group(function () {
            Route::post('login', 'login');
            Route::post('check-token', 'checkToken');
            Route::post('social-login', 'socialLogin');

            // Route::post('redirect','redirect');
            // Route::get('callback', [LoginController::class, 'callback']);
                Route::post('apple-auth','apple_auth');
            Route::post('callback', 'apple_callback');
        });
        Route::post('register', 'RegisterController@register');
        Route::controller('ForgotPasswordController')->group(function () {
            Route::post('password/email', 'sendResetCodeEmail');
            Route::post('password/verify-code', 'verifyCode');
            Route::post('password/reset', 'reset');
        });
    });

    Route::middleware(['auth:sanctum', 'token.permission:auth_token'])->group(function () {

        Route::post('user-data-submit', 'UserController@userDataSubmit');

        //authorization
        Route::middleware('registration.complete')->controller('AuthorizationController')->group(function () {
            Route::get('authorization', 'authorization');
            Route::get('resend-verify/{type}', 'sendVerifyCode');
            Route::post('verify-email', 'emailVerification');
            Route::post('verify-mobile', 'mobileVerification');
        });

        Route::middleware(['registration.complete', 'check.status'])->group(function () {
            Route::controller('UserController')->group(function () {
                Route::get('dashboard', 'dashboard');
                Route::post('profile-setting', 'submitProfile');
                Route::post('change-password', 'submitPassword');

                Route::get('user-info', 'userInfo');

                //Report

                Route::any('payment/history', 'paymentHistory');

                Route::post('save-device-token', 'addDeviceToken');
                Route::get('push-notifications', 'pushNotifications');
                Route::post('push-notifications/read/{id}', 'pushNotificationsRead');


                Route::post('delete-account', 'deleteAccount');

                Route::post('pusher/auth/{socketId}/{channelName}', 'pusher');
                Route::get('get-waste-types', 'getWasteTypes');
                
            });

            Route::prefix('ride')->controller('RideController')->group(function () {
                Route::post('fare-and-distance', 'findFareAndDistance');
                Route::post('create', 'create');
                Route::get('bids/{id}', 'bids');
                Route::post('reject/{id}', 'reject');
                Route::post('accept/{bidId}', 'accept');
                Route::get('list', 'list');
                Route::post('cancel/{id}', 'cancel');
                Route::post('sos/{id}', 'sos');
                Route::get('details/{id}', 'details');
                Route::get('payment/{id}', 'payment');
                Route::post('payment/{id}', 'paymentSave');
            });

            Route::prefix('pickup')->controller('WasteCollectionController')->group(callback: function () {
                Route::post('fare-and-distance', 'findFareAndDistance');
                Route::post('create', 'create');
                Route::get('bids/{id}', 'bids');
                Route::post('reject/{id}', 'reject');
                Route::post('accept/{bidId}', 'accept');
                Route::get('list', 'list');
                Route::post('cancel/{id}', 'cancel');
                Route::post('sos/{id}', 'sos');
                Route::get('details/{id}', 'details');
                Route::get('payment/{id}', 'payment');
                Route::post('confrim-payment', 'ConfirmPayment');
                Route::post('payment/{id}', 'paymentSave');
                Route::post('find-waste-amount', 'findWasteAmount');
                Route::get('collector-details/{id}', 'collectorDetails');
                Route::get('pickup-status/{status}', 'pickupStatus');
                Route::get('get-pickup-status/{id}', 'getPickupStatus');
            });

            // Coupon
            Route::controller('CouponController')->group(function () {
                Route::get('coupons', 'coupons');
                Route::post('apply-coupon/{id}', 'applyCoupon');
                Route::post('remove-coupon/{id}', 'removeCoupon');
            });

            Route::controller('ReviewController')->group(function () {
                Route::get('review', 'review');
                Route::post('review/{id}', 'reviewStore');
                Route::get('get-driver-review/{driverId}', 'driverReview');
            });

            Route::controller('TicketController')->prefix('ticket')->group(function () {
                Route::get('/', 'supportTicket');
                Route::post('create', 'storeSupportTicket');
                Route::get('view/{ticket}', 'viewTicket');
                Route::post('reply/{id}', 'replyTicket');
                Route::post('close/{id}', 'closeTicket');
                Route::get('download/{attachment_id}', 'ticketDownload');
            });
            //message
            Route::controller('MessageController')->prefix('ride')->group(function () {
                Route::get('messages/{id}', 'messages');
                Route::post('send/message/{id}', 'messageSave');
            });
        });
        Route::get('logout', 'Auth\LoginController@logout');
    });
});

//start driver route
Route::namespace('Api\Driver')->prefix('collector')->group(function () {
    Route::namespace('Auth')->group(function () {
        Route::controller('LoginController')->group(function () {
            Route::post('login', 'login');
            Route::post('social-login', 'socialLogin');
        });
        Route::post('register', 'RegisterController@register');

        Route::controller('ForgotPasswordController')->group(function () {
            Route::post('password/email', 'sendResetCodeEmail');
            Route::post('password/verify-code', 'verifyCode');
            Route::post('password/reset', 'reset');
        });
    });

    Route::middleware(['auth:sanctum', 'token.permission:collector_token'])->group(function () {
        //authorization
        Route::post('collector-data-submit', 'DriverController@collectorDataSubmit');
        Route::middleware('registration.complete')->group(function () {
            Route::controller('AuthorizationController')->group(function () {
                Route::get('authorization', 'authorization');
                Route::get('resend-verify/{type}', 'sendVerifyCode');
                Route::post('verify-email', 'emailVerification');
                Route::post('verify-mobile', 'mobileVerification');
                Route::post('verify-g2fa', 'g2faVerification');
            });

            Route::middleware(['check.status'])->group(function () {
                Route::controller('DriverController')->group(function () {
                    Route::get('dashboard', 'dashboard');
                    Route::get('collector-info', 'collectorInfo');

                    Route::post('profile-setting', 'submitProfile');
                    Route::post('change-password', 'submitPassword');
                    Route::post('delete-account', 'accountDelete');

                    Route::post('pusher/auth/{socketId}/{channelName}', 'pusher');
                    //Driver Verification
                    Route::get('collector-verification', 'collectorVerification');
                    Route::post('collector-verification', 'collectorVerificationStore');

                    //vehicle verification 
                    Route::get('vehicle-verification', 'vehicleVerification');
                    Route::post('vehicle-verification', 'vehicleVerificationStore');

                    //Report
                    Route::any('deposit/history', 'depositHistory');
                    Route::get('transactions', 'transactions');
                    Route::get('payment/history', 'paymentHistory');
                    Route::post('online-status', 'onlineStatus');

                    Route::post('save-device-token', 'addDeviceToken');

                    //2FA
                    Route::get('twofactor', 'show2faForm');
                    Route::post('twofactor/enable', 'create2fa');
                    Route::post('twofactor/disable', 'disable2fa');
                    Route::get('get-waste-types', 'getWasteTypes');
                });

                Route::controller('ReviewController')->group(function () {
                    Route::get('review', 'review');
                    Route::post('review/{rideId}', 'reviewStore');
                    Route::get('get-rider-review/{riderId}', 'riderReview');
                });

                //Withdraw
                Route::middleware('collector.verification')->group(function () {
                    Route::controller('WithdrawController')->group(function () {
                        Route::get('withdraw-method', 'withdrawMethod');
                        Route::post('withdraw-request', 'withdrawStore');
                        Route::post('withdraw-request/confirm', 'withdrawSubmit');
                        Route::get('withdraw/history', 'withdrawLog');
                    });
                    // Rides
                    Route::controller('RideController')->prefix('rides')->group(function () {
                        Route::get('/', 'rides');
                        Route::get('details/{id}', 'details');
                        Route::post('start/{id}', 'start');
                        Route::post('end/{id}', 'end');
                        Route::get('list/{id}', 'list');
                        Route::post('received-cash-payment/{id}', 'receivedCashPayment');
                        Route::post('live-location/{id}', 'liveLocation');
                    });

                    Route::controller('WasteCollectionController')->prefix('pickup')->group(function () {
                        Route::get('details/{id}', 'details');
                        Route::post('start/{id}', 'start');
                        Route::post('end/{id}', 'end');
                        Route::get('list', 'list');
                        Route::post('received-cash-payment/{id}', 'receivedCashPayment');
                        Route::post('live-location', 'liveLocation');
                        Route::post('accept-pickup/{id}', 'acceptPickup');
                        Route::post('reject-pickup/{id}', 'rejectPickup');
                        Route::get('pickup-status/{status}', 'pickupStatus');
                        Route::get('get-pickup-status/{id}', 'getPickupStatus');
                        Route::get('get-payment-status/{id}', 'getPaymentStatus');
                    });

                    //Bid
                    Route::controller('BidController')->prefix('bid')->group(function () {
                        Route::post('create/{id}', 'create');
                        Route::get('cancel/{id}', 'cancel');
                    });
                    
                    //message
                    Route::controller('MessageController')->prefix('ride')->group(function () {
                        Route::get('messages/{id}', 'messages');
                        Route::post('send/message/{id}', 'messageSave');
                    });
                });
                //payment
                Route::controller('PaymentController')->group(function () {
                    Route::get('deposit/methods', 'methods');
                    Route::post('deposit/insert', 'depositInsert');
                });
                //ticket
                Route::controller('TicketController')->prefix('ticket')->group(function () {
                    Route::get('/', 'supportTicket');
                    Route::post('create', 'storeSupportTicket');
                    Route::get('view/{ticket}', 'viewTicket');
                    Route::post('reply/{id}', 'replyTicket');
                    Route::post('close/{id}', 'closeTicket');
                    Route::get('download/{attachment_id}', 'ticketDownload');
                });
            });
        });
        Route::get('logout', 'Auth\LoginController@logout');
    });
});
