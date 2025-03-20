<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DownloadLog;
use Illuminate\Support\Facades\Auth;

class DownloadLogController extends Controller
{
    public function logDownload(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Save download log
        DownloadLog::create([
            'user_id' => $user->id,
            'file_name' => $request->file_name,
            'downloaded_at' => now(),
        ]);

        return response()->json(['message' => 'Download logged successfully']);
    }

    public function getLastDownload()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Get the last download for the authenticated user
        $lastDownload = DownloadLog::where('user_id', $user->id)
            ->orderBy('downloaded_at', 'desc')
            ->first();

        return response()->json([
            'message' => 'Last download fetched successfully',
            'last_download' => $lastDownload ? $lastDownload->downloaded_at : null,
            'file_name' => $lastDownload ? $lastDownload->file_name : null
        ]);
    }
}
