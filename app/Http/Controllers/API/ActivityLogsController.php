<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Validator;

class ActivityLogsController extends Controller
{
    /**
     * Store activity logs from the frontend.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logs' => 'required|array',
            'logs.*.action' => 'required|string',
            'logs.*.timestamp' => 'required|date',
            'logs.*.url' => 'required|string',
            'logs.*.metadata' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Get authenticated user if available
        $user = $request->user();
        $token = $request->bearerToken();

        if ($token && !$user) {
            try {
                $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                if ($accessToken) {
                    $user = $accessToken->tokenable;
                }
            } catch (\Exception $e) {
            }
        }
        // Process all logs in the batch
        foreach ($request->logs as $log) {
            activity('frontend')
                ->causedBy($user)
                ->withProperties([
                    'url' => $log['url'],
                    'timestamp' => $log['timestamp'],
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => $log['metadata'],
                ])
                ->log($log['action']);
        }

        return response()->json(['status' => 'success'], 200);
    }
}
