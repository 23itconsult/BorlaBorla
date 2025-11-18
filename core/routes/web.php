<?php

use Illuminate\Support\Facades\Route;
use Firebase\JWT\JWT;

Route::get('/test-apple-jwt', function () {
    $keyId = env('APPLE_KEY_ID');
    $teamId = env('APPLE_TEAM_ID');
    $clientId = env('APPLE_CLIENT_ID');

    // Full path to the key in public directory
  $privateKeyPath = storage_path('Apple_auth/AuthKey_'.env('APPLE_KEY_ID').'.p8');

if (!file_exists($privateKeyPath)) {
    throw new \Exception("Private key not found at: {$privateKeyPath}");
}
    $privateKey = file_get_contents($privateKeyPath);

    $payload = [
        'iss' => $teamId,
        'iat' => time(),
        'exp' => time() + (86400 * 180), // 6 months
        'aud' => 'https://borlaborla.com/admin',
        'sub' => $clientId,
    ];

    $clientSecret = JWT::encode($payload, $privateKey, 'ES256', $keyId);

    return response()->json([
        'client_secret' => $clientSecret,
    ]);
});

Route::get('/clear', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
});

Route::get('app/deposit/confirm/{hash}', 'Gateway\PaymentController@appDepositConfirm')->name('deposit.app.confirm');
Route::get('cron', 'CronController@cron')->name('cron');

Route::controller('TicketController')->prefix('ticket')->name('ticket.')->group(function () {
    Route::get('view/{ticket}', 'viewTicket')->name('view');
    Route::post('reply/{id}', 'replyTicket')->name('reply');
    Route::post('close/{id}', 'closeTicket')->name('close');
    Route::get('download/{attachment_id}', 'ticketDownload')->name('download');
});

Route::controller('SiteController')->group(function () {
    Route::get('/contact', 'contact')->name('contact');
    Route::post('/contact', 'contactSubmit');
    Route::get('/change/{lang?}', 'changeLanguage')->name('lang');
    Route::post('subscribe', 'subscribe')->name('subscribe');

    Route::get('cookie-policy', 'cookiePolicy')->name('cookie.policy');

    Route::get('/cookie/accept', 'cookieAccept')->name('cookie.accept');

    Route::get('blog', 'blog')->name('blog');
    Route::get('blog/{slug}', 'blogDetails')->name('blog.details');

    Route::get('policy/{slug}', 'policyPages')->name('policy.pages');

    Route::get('placeholder-image/{size}', 'placeholderImage')->withoutMiddleware('maintenance')->name('placeholder.image');
    Route::get('maintenance-mode', 'maintenance')->withoutMiddleware('maintenance')->name('maintenance');

    Route::get('/{slug}', 'pages')->name('pages');
    Route::get('/', 'index')->name('home');
});
