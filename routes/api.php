<?php

use App\Http\Controllers\VisitorsOnlineController;
use Illuminate\Support\Facades\Route;

Route::get('visitors/online', VisitorsOnlineController::class)->name('visitors.online');
