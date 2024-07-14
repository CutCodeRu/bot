<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', static function () {
   abort(403);
});

Route::post('webhook/{bot}', WebhookController::class)->name('webhook');
