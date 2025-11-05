<?php

namespace App\Http\Controllers;

use App\Services\OnlineVisitors;
use Illuminate\Http\JsonResponse;

class VisitorsOnlineController extends Controller
{
    public function __invoke(OnlineVisitors $visitors): JsonResponse
    {
        return response()->json($visitors->all());
    }
}
